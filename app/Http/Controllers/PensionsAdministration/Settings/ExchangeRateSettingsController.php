<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionReceipt;
use App\Models\PensionsAdministration\Contributions\ExchangeRate;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ExchangeRateSettingsController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        $rates = ExchangeRate::query()
            ->withCount(['receipts as usage_count'])
            ->orderByDesc('rate_date')
            ->orderBy('from_currency')
            ->orderBy('to_currency')
            ->get();

        $summary = [
            'total' => ExchangeRate::count(),
            'used' => ExchangeRate::whereHas('receipts')->count(),
            'unused' => ExchangeRate::whereDoesntHave('receipts')->count(),
            'latest_date' => ExchangeRate::max('rate_date'),
        ];

        return view('pensions-administration.settings.exchange-rates.index', compact('rates', 'summary'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.exchange-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateRate($request);

        $fromCurrency = strtoupper(trim($validated['from_currency']));
        $toCurrency = strtoupper(trim($validated['to_currency']));

        if ($fromCurrency === $toCurrency) {
            throw ValidationException::withMessages([
                'to_currency' => 'The destination currency must be different from the source currency.',
            ]);
        }

        $exists = ExchangeRate::query()
            ->whereDate('rate_date', $validated['rate_date'])
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'rate_date' => 'An exchange rate already exists for this date and currency pair.',
            ]);
        }

        try {
            DB::transaction(function () use ($request, $validated, $fromCurrency, $toCurrency): void {
                $rate = ExchangeRate::create([
                    'rate_date' => $validated['rate_date'],
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'rate' => $validated['rate'],
                    'source' => $validated['source'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'exchange_rate_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Exchange rate created.',
                    auditable: $rate,
                    oldValues: null,
                    newValues: $this->auditService->values($rate),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'currency_pair' => "{$fromCurrency}/{$toCurrency}",
                        'rate_date' => $validated['rate_date'],
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.exchange-rates.index')->with('success', 'Exchange rate created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'exchange_rate_create_failed',
                action: 'create',
                description: 'Failed to create exchange rate.',
                exception: $exception,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to create the exchange rate.');
        }
    }

    public function show(ExchangeRate $exchangeRate): View
    {
        $usageCount = ContributionReceipt::query()->where('exchange_rate_id', $exchangeRate->id)->count();

        $recentReceipts = ContributionReceipt::query()
            ->where('exchange_rate_id', $exchangeRate->id)
            ->orderByDesc('receipt_date')
            ->limit(20)
            ->get();

        return view('pensions-administration.settings.exchange-rates.show', compact('exchangeRate', 'usageCount', 'recentReceipts'));
    }

    public function edit(ExchangeRate $exchangeRate): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if ($this->isUsed($exchangeRate)) {
            return redirect()->route('pensions-administration.settings.exchange-rates.index')->with('error', 'This exchange rate has already been used by posted receipts and is locked.');
        }

        return view('pensions-administration.settings.exchange-rates.edit', compact('exchangeRate'));
    }

    public function update(Request $request, ExchangeRate $exchangeRate): RedirectResponse
    {
        $this->ensureManagePermission();

        if ($this->isUsed($exchangeRate)) {
            return redirect()->route('pensions-administration.settings.exchange-rates.index')->with('error', 'This exchange rate has already been used by posted receipts and cannot be changed.');
        }

        $validated = $this->validateRate($request);

        $fromCurrency = strtoupper(trim($validated['from_currency']));
        $toCurrency = strtoupper(trim($validated['to_currency']));

        if ($fromCurrency === $toCurrency) {
            throw ValidationException::withMessages([
                'to_currency' => 'The destination currency must be different from the source currency.',
            ]);
        }

        $duplicate = ExchangeRate::query()
            ->whereKeyNot($exchangeRate->id)
            ->whereDate('rate_date', $validated['rate_date'])
            ->where('from_currency', $fromCurrency)
            ->where('to_currency', $toCurrency)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'rate_date' => 'Another exchange rate already exists for this date and currency pair.',
            ]);
        }

        $oldValues = $this->auditService->values($exchangeRate);

        try {
            DB::transaction(function () use ($request, $exchangeRate, $validated, $fromCurrency, $toCurrency, $oldValues): void {
                if ($this->isUsed($exchangeRate)) {
                    throw ValidationException::withMessages([
                        'rate' => 'This exchange rate became referenced by a receipt and can no longer be changed.',
                    ]);
                }

                $exchangeRate->update([
                    'rate_date' => $validated['rate_date'],
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'rate' => $validated['rate'],
                    'source' => $validated['source'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $exchangeRate->refresh();

                $this->auditService->log(
                    eventType: 'exchange_rate_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Unused exchange rate corrected.',
                    auditable: $exchangeRate,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($exchangeRate),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'currency_pair' => "{$fromCurrency}/{$toCurrency}",
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.exchange-rates.index')->with('success', 'Exchange rate updated successfully.');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'exchange_rate_update_failed',
                action: 'update',
                description: 'Failed to update exchange rate.',
                exception: $exception,
                exchangeRate: $exchangeRate,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to update the exchange rate.');
        }
    }

    private function validateRate(Request $request): array
    {
        return $request->validate([
            'rate_date' => ['required', 'date'],
            'from_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'to_currency' => ['required', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
            'rate' => ['required', 'numeric', 'gt:0'],
            'source' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function isUsed(ExchangeRate $exchangeRate): bool
    {
        return ContributionReceipt::query()
            ->where('exchange_rate_id', $exchangeRate->id)
            ->exists();
    }

    private function ensureManagePermission(): void
    {
        $user = auth()->user();

        abort_unless(
            $user
            && $user->hasRole('system-administrator')
            && $user->can('pensions.settings.manage'),
            403
        );
    }

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?ExchangeRate $exchangeRate = null, ?array $metadata = null): void
    {
        try {
            $this->auditService->record(
                eventType: $eventType,
                module: 'pensions-benefit-settings',
                action: $action,
                description: $description,
                subject: $exchangeRate,
                oldValues: $exchangeRate ? $this->auditService->values($exchangeRate) : null,
                newValues: null,
                metadata: $metadata,
                outcome: 'failed',
                failureReason: $exception->getMessage(),
                request: $request
            );
        } catch (Throwable) {
        }
    }
}