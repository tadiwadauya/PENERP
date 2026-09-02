<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\InterestRate;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class InterestRateController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        $rates = InterestRate::query()->orderByDesc('effective_from')->get();

        $today = now()->startOfDay();

        $summary = [
            'total' => $rates->count(),
            'current' => $rates->first(fn ($rate) => $rate->is_active && $rate->effective_from->lte($today) && (!$rate->effective_to || $rate->effective_to->gte($today))),
            'historical' => $rates->filter(fn ($rate) => $rate->effective_to && $rate->effective_to->lt($today))->count(),
            'future' => $rates->filter(fn ($rate) => $rate->effective_from->gt($today))->count(),
        ];

        return view('pensions-administration.settings.interest-rates.index', compact('rates', 'summary'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.interest-rates.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateRate($request);
        $this->ensureNoOverlap($validated['effective_from'], $validated['effective_to'] ?? null);

        try {
            DB::transaction(function () use ($request, $validated): void {
                $rate = InterestRate::create([
                    'rate_percentage' => $validated['rate_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'interest_rate_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Interest rate created.',
                    auditable: $rate,
                    oldValues: null,
                    newValues: $this->auditService->values($rate),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('success', 'Interest rate created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'interest_rate_create_failed', 'create', 'Failed to create interest rate.', $exception, null, $validated);

            return back()->withInput()->with('error', 'Unable to create the interest rate.');
        }
    }

    public function show(InterestRate $interestRate): View
    {
        return view('pensions-administration.settings.interest-rates.show', compact('interestRate'));
    }

    public function edit(InterestRate $interestRate): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$interestRate->effective_from->isFuture()) {
            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('error', 'Historical and current interest rates cannot be edited directly. Create a new version instead.');
        }

        return view('pensions-administration.settings.interest-rates.edit', compact('interestRate'));
    }

    public function update(Request $request, InterestRate $interestRate): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$interestRate->effective_from->isFuture()) {
            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('error', 'Only future-dated interest rates may be edited directly.');
        }

        $validated = $this->validateRate($request);
        $this->ensureNoOverlap($validated['effective_from'], $validated['effective_to'] ?? null, $interestRate->id);

        $oldValues = $this->auditService->values($interestRate);

        try {
            DB::transaction(function () use ($request, $interestRate, $validated, $oldValues): void {
                $interestRate->update([
                    'rate_percentage' => $validated['rate_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $interestRate->refresh();

                $this->auditService->log(
                    eventType: 'interest_rate_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Future interest rate updated.',
                    auditable: $interestRate,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($interestRate),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('success', 'Interest rate updated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'interest_rate_update_failed', 'update', 'Failed to update interest rate.', $exception, $interestRate, $validated);

            return back()->withInput()->with('error', 'Unable to update the interest rate.');
        }
    }

    public function createVersion(InterestRate $interestRate): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if ($interestRate->effective_to && $interestRate->effective_to->lt(today())) {
            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('error', 'A historical interest rate cannot be versioned.');
        }

        return view('pensions-administration.settings.interest-rates.version', compact('interestRate'));
    }

    public function storeVersion(Request $request, InterestRate $interestRate): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $request->validate([
            'rate_percentage' => ['required', 'numeric', 'gte:0'],
            'effective_from' => ['required', 'date', 'after:' . $interestRate->effective_from->format('Y-m-d')],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $newFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        $this->ensureNoOverlap(
            $newFrom->toDateString(),
            null,
            $interestRate->id
        );

        $oldValues = $this->auditService->values($interestRate);

        try {
            DB::transaction(function () use ($request, $interestRate, $validated, $newFrom, $oldValues): void {
                $interestRate->update([
                    'effective_to' => $newFrom->copy()->subDay()->toDateString(),
                    'updated_by' => auth()->id(),
                ]);

                $newRate = InterestRate::create([
                    'rate_percentage' => $validated['rate_percentage'],
                    'effective_from' => $newFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? $interestRate->source_authority,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'interest_rate_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'New interest rate version created.',
                    auditable: $newRate,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($newRate),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'previous_rate_id' => $interestRate->id,
                        'previous_effective_to' => $interestRate->effective_to?->format('Y-m-d'),
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.interest-rates.index')->with('success', 'New interest rate version created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'interest_rate_version_failed', 'version', 'Failed to create new interest rate version.', $exception, $interestRate, $validated);

            return back()->withInput()->with('error', 'Unable to create the new interest rate version.');
        }
    }

    public function deactivate(Request $request, InterestRate $interestRate): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$interestRate->effective_from->isFuture()) {
            return back()->with('error', 'Only future-dated interest rates may be deactivated.');
        }

        $validated = $request->validate([
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $oldValues = $this->auditService->values($interestRate);

        try {
            DB::transaction(function () use ($request, $interestRate, $validated, $oldValues): void {
                $interestRate->update([
                    'is_active' => false,
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'interest_rate_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: 'Future interest rate deactivated.',
                    auditable: $interestRate,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($interestRate),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return back()->with('success', 'Interest rate deactivated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'interest_rate_deactivate_failed', 'deactivate', 'Failed to deactivate interest rate.', $exception, $interestRate, $validated);

            return back()->with('error', 'Unable to deactivate the interest rate.');
        }
    }

    private function validateRate(Request $request): array
    {
        return $request->validate([
            'rate_percentage' => ['required', 'numeric', 'gte:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function ensureNoOverlap(string $effectiveFrom, ?string $effectiveTo = null, ?int $excludeId = null): void
    {
        $from = Carbon::parse($effectiveFrom)->startOfDay();
        $to = $effectiveTo ? Carbon::parse($effectiveTo)->startOfDay() : null;

        $query = InterestRate::query()->where('is_active', true);

        if ($excludeId) {
            $query->whereKeyNot($excludeId);
        }

        $overlap = $query
            ->whereDate('effective_from', '<=', $to?->toDateString() ?? '9999-12-31')
            ->where(function ($query) use ($from): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from->toDateString());
            })
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' => 'The effective period overlaps an existing active interest rate.',
            ]);
        }
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

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?InterestRate $interestRate = null, ?array $metadata = null): void
    {
        try {
            $this->auditService->record(
                eventType: $eventType,
                module: 'pensions-benefit-settings',
                action: $action,
                description: $description,
                subject: $interestRate,
                oldValues: $interestRate ? $this->auditService->values($interestRate) : null,
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