<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\BenefitTaxTable;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BenefitTaxTableController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        $taxTables = BenefitTaxTable::query()
            ->withCount('bands')
            ->orderByDesc('effective_from')
            ->orderBy('tax_year')
            ->orderBy('benefit_type')
            ->get();

        $summary = [
            'total' => BenefitTaxTable::count(),
            'active' => BenefitTaxTable::where('is_active', true)->count(),
            'current' => BenefitTaxTable::where('is_active', true)
                ->whereDate('effective_from', '<=', today())
                ->where(function ($q): void {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today());
                })->count(),
            'future' => BenefitTaxTable::where('is_active', true)->whereDate('effective_from', '>', today())->count(),
        ];

        return view('pensions-administration.settings.tax-tables.index', compact('taxTables', 'summary'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.tax-tables.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateTaxTable($request);
        $bands = $this->normaliseAndValidateBands($validated['bands']);

        $this->ensureNoDateOverlap(
            taxYear: $validated['tax_year'],
            currency: strtoupper($validated['currency']),
            benefitType: $validated['benefit_type'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null
        );

        try {
            DB::transaction(function () use ($request, $validated, $bands): void {
                $taxTable = BenefitTaxTable::create([
                    'name' => $validated['name'],
                    'tax_year' => $validated['tax_year'],
                    'currency' => strtoupper($validated['currency']),
                    'benefit_type' => $validated['benefit_type'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->createBands($taxTable, $bands);

                $taxTable->load('bands');

                $this->auditService->log(
                    eventType: 'benefit_tax_table_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Benefit tax table and tax bands created.',
                    auditable: $taxTable,
                    oldValues: null,
                    newValues: $this->snapshot($taxTable),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('success', 'Tax table created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'benefit_tax_table_create_failed', 'create', 'Failed to create benefit tax table.', $exception, null, $validated);
            return back()->withInput()->with('error', 'Unable to create the tax table.');
        }
    }

    public function show(BenefitTaxTable $taxTable): View
    {
        $taxTable->load('bands');

        return view('pensions-administration.settings.tax-tables.show', compact('taxTable'));
    }

    public function edit(BenefitTaxTable $taxTable): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$taxTable->is_active) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'Inactive tax tables cannot be edited.');
        }

        if ($this->hasTakenEffect($taxTable)) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'This tax table has already taken effect. Create a new version instead.');
        }

        $taxTable->load('bands');

        return view('pensions-administration.settings.tax-tables.edit', compact('taxTable'));
    }

    public function update(Request $request, BenefitTaxTable $taxTable): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$taxTable->is_active || $this->hasTakenEffect($taxTable)) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'This tax table cannot be edited.');
        }

        $validated = $this->validateTaxTable($request);
        $bands = $this->normaliseAndValidateBands($validated['bands']);

        $this->ensureNoDateOverlap(
            taxYear: $validated['tax_year'],
            currency: strtoupper($validated['currency']),
            benefitType: $validated['benefit_type'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null,
            ignoreId: $taxTable->id
        );

        $taxTable->load('bands');
        $oldValues = $this->snapshot($taxTable);

        try {
            DB::transaction(function () use ($request, $taxTable, $validated, $bands, $oldValues): void {
                $taxTable->update([
                    'name' => $validated['name'],
                    'tax_year' => $validated['tax_year'],
                    'currency' => strtoupper($validated['currency']),
                    'benefit_type' => $validated['benefit_type'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $taxTable->bands()->delete();
                $this->createBands($taxTable, $bands);

                $taxTable->refresh()->load('bands');

                $this->auditService->log(
                    eventType: 'benefit_tax_table_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Future benefit tax table and tax bands updated.',
                    auditable: $taxTable,
                    oldValues: $oldValues,
                    newValues: $this->snapshot($taxTable),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('success', 'Tax table updated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'benefit_tax_table_update_failed', 'update', 'Failed to update benefit tax table.', $exception, $taxTable, $validated);
            return back()->withInput()->with('error', 'Unable to update the tax table.');
        }
    }

    public function createVersion(BenefitTaxTable $taxTable): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$taxTable->is_active || !$this->hasTakenEffect($taxTable) || $taxTable->effective_to || !$this->isLatestVersion($taxTable)) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'This tax table cannot be versioned.');
        }

        $taxTable->load('bands');

        return view('pensions-administration.settings.tax-tables.version', compact('taxTable'));
    }

    public function storeVersion(Request $request, BenefitTaxTable $taxTable): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$taxTable->is_active || !$this->hasTakenEffect($taxTable) || $taxTable->effective_to || !$this->isLatestVersion($taxTable)) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'This tax table cannot be versioned.');
        }

        $validated = $this->validateVersion($request);
        $bands = $this->normaliseAndValidateBands($validated['bands']);

        $newEffectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        if ($newEffectiveFrom->lt(today())) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new version effective date cannot be earlier than today.',
            ]);
        }

        if ($newEffectiveFrom->lte($taxTable->effective_from->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new effective date must be after the existing tax table effective date.',
            ]);
        }

        $taxTable->load('bands');
        $oldValues = $this->snapshot($taxTable);

        try {
            DB::transaction(function () use ($request, $taxTable, $validated, $bands, $newEffectiveFrom, $oldValues): void {
                $taxTable->update([
                    'effective_to' => $newEffectiveFrom->copy()->subDay()->toDateString(),
                    'updated_by' => auth()->id(),
                ]);

                $taxTable->refresh()->load('bands');

                $this->auditService->log(
                    eventType: 'benefit_tax_table_version_closed',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'Previous benefit tax table version closed.',
                    auditable: $taxTable,
                    oldValues: $oldValues,
                    newValues: $this->snapshot($taxTable),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'new_version_effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                $newTaxTable = BenefitTaxTable::create([
                    'name' => $validated['name'],
                    'tax_year' => $validated['tax_year'],
                    'currency' => strtoupper($validated['currency']),
                    'benefit_type' => $validated['benefit_type'],
                    'effective_from' => $newEffectiveFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? $taxTable->source_authority,
                    'notes' => $validated['notes'] ?? $taxTable->notes,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->createBands($newTaxTable, $bands);
                $newTaxTable->load('bands');

                $this->auditService->log(
                    eventType: 'benefit_tax_table_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'New benefit tax table version created.',
                    auditable: $newTaxTable,
                    oldValues: null,
                    newValues: $this->snapshot($newTaxTable),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'previous_version_id' => $taxTable->id,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('success', 'New tax table version created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'benefit_tax_table_version_failed', 'version', 'Failed to create benefit tax table version.', $exception, $taxTable, $validated);
            return back()->withInput()->with('error', 'Unable to create the new tax table version.');
        }
    }

    public function deactivate(Request $request, BenefitTaxTable $taxTable): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$taxTable->is_active) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'This tax table is already inactive.');
        }

        if ($this->hasTakenEffect($taxTable)) {
            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('error', 'A tax table that has already taken effect cannot be deactivated.');
        }

        $validated = $request->validate([
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $taxTable->load('bands');
        $oldValues = $this->snapshot($taxTable);

        try {
            DB::transaction(function () use ($request, $taxTable, $validated, $oldValues): void {
                $taxTable->update([
                    'is_active' => false,
                    'updated_by' => auth()->id(),
                ]);

                $taxTable->refresh()->load('bands');

                $this->auditService->log(
                    eventType: 'benefit_tax_table_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: 'Future benefit tax table deactivated.',
                    auditable: $taxTable,
                    oldValues: $oldValues,
                    newValues: $this->snapshot($taxTable),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.tax-tables.index')->with('success', 'Tax table deactivated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'benefit_tax_table_deactivate_failed', 'deactivate', 'Failed to deactivate benefit tax table.', $exception, $taxTable, $validated);
            return back()->with('error', 'Unable to deactivate the tax table.');
        }
    }

    private function validateTaxTable(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'tax_year' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string', 'max:10'],
            'benefit_type' => ['required', 'string', 'max:80'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
            'bands' => ['required', 'array', 'min:1'],
            'bands.*.lower_limit' => ['required', 'numeric', 'min:0'],
            'bands.*.upper_limit' => ['nullable', 'numeric', 'gt:bands.*.lower_limit'],
            'bands.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.fixed_amount' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function validateVersion(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'tax_year' => ['required', 'string', 'max:20'],
            'currency' => ['required', 'string', 'max:10'],
            'benefit_type' => ['required', 'string', 'max:80'],
            'effective_from' => ['required', 'date'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
            'bands' => ['required', 'array', 'min:1'],
            'bands.*.lower_limit' => ['required', 'numeric', 'min:0'],
            'bands.*.upper_limit' => ['nullable', 'numeric'],
            'bands.*.rate_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'bands.*.fixed_amount' => ['required', 'numeric', 'min:0'],
        ]);
    }

    private function normaliseAndValidateBands(array $bands): array
    {
        $bands = array_values($bands);

        foreach ($bands as $index => &$band) {
            $band['band_order'] = $index + 1;
            $band['lower_limit'] = (float) $band['lower_limit'];
            $band['upper_limit'] = filled($band['upper_limit'] ?? null) ? (float) $band['upper_limit'] : null;
            $band['rate_percentage'] = (float) $band['rate_percentage'];
            $band['fixed_amount'] = (float) $band['fixed_amount'];

            if ($band['upper_limit'] !== null && $band['upper_limit'] <= $band['lower_limit']) {
                throw ValidationException::withMessages([
                    "bands.$index.upper_limit" => 'Upper limit must be greater than the lower limit.',
                ]);
            }

            if ($index > 0) {
                $previous = $bands[$index - 1];

                if ($previous['upper_limit'] === null) {
                    throw ValidationException::withMessages([
                        "bands.$index.lower_limit" => 'No tax band may follow an open-ended tax band.',
                    ]);
                }

                if ($band['lower_limit'] < (float) $previous['upper_limit']) {
                    throw ValidationException::withMessages([
                        "bands.$index.lower_limit" => 'Tax bands cannot overlap.',
                    ]);
                }
            }
        }

        unset($band);

        $lastIndex = count($bands) - 1;

        foreach ($bands as $index => $band) {
            if ($index !== $lastIndex && $band['upper_limit'] === null) {
                throw ValidationException::withMessages([
                    "bands.$index.upper_limit" => 'Only the final tax band may be open-ended.',
                ]);
            }
        }

        return $bands;
    }

    private function createBands(BenefitTaxTable $taxTable, array $bands): void
    {
        foreach ($bands as $band) {
            $taxTable->bands()->create([
                'band_order' => $band['band_order'],
                'lower_limit' => $band['lower_limit'],
                'upper_limit' => $band['upper_limit'],
                'rate_percentage' => $band['rate_percentage'],
                'fixed_amount' => $band['fixed_amount'],
            ]);
        }
    }

    private function ensureNoDateOverlap(string $taxYear, string $currency, string $benefitType, string $effectiveFrom, ?string $effectiveTo = null, ?int $ignoreId = null): void
    {
        $newFrom = Carbon::parse($effectiveFrom)->toDateString();
        $newTo = $effectiveTo ? Carbon::parse($effectiveTo)->toDateString() : null;

        $query = BenefitTaxTable::query()
            ->where('tax_year', $taxYear)
            ->where('currency', strtoupper($currency))
            ->where('benefit_type', $benefitType)
            ->where('is_active', true);

        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);
        if ($newTo !== null) $query->whereDate('effective_from', '<=', $newTo);

        $query->where(function ($q) use ($newFrom): void {
            $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $newFrom);
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'An active tax table for this tax year, currency and benefit type already overlaps the selected effective period.',
            ]);
        }
    }

    private function isLatestVersion(BenefitTaxTable $taxTable): bool
    {
        return !BenefitTaxTable::query()
            ->where('currency', $taxTable->currency)
            ->where('benefit_type', $taxTable->benefit_type)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $taxTable->effective_from)
            ->exists();
    }

    private function hasTakenEffect(BenefitTaxTable $taxTable): bool
    {
        return $taxTable->effective_from?->copy()->startOfDay()->lte(today()) ?? false;
    }

    private function snapshot(BenefitTaxTable $taxTable): array
    {
        return [
            ...$this->auditService->values($taxTable),
            'bands' => $taxTable->bands->map(fn ($band) => [
                'band_order' => $band->band_order,
                'lower_limit' => $band->lower_limit,
                'upper_limit' => $band->upper_limit,
                'rate_percentage' => $band->rate_percentage,
                'fixed_amount' => $band->fixed_amount,
            ])->values()->all(),
        ];
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

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?BenefitTaxTable $taxTable = null, ?array $metadata = null): void
    {
        try {
            $this->auditService->record(
                eventType: $eventType,
                module: 'pensions-benefit-settings',
                action: $action,
                description: $description,
                subject: $taxTable,
                oldValues: $taxTable ? $this->snapshot($taxTable->load('bands')) : null,
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