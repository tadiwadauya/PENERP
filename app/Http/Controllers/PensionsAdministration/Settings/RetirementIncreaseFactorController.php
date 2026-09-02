<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\RetirementAgeIncreaseFactor;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RetirementIncreaseFactorController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(): View
    {
        $factors = RetirementAgeIncreaseFactor::query()
            ->orderBy('age_years')
            ->orderByDesc('effective_from')
            ->get();

        $summary = [
            'total' => RetirementAgeIncreaseFactor::count(),
            'active' => RetirementAgeIncreaseFactor::where('is_active', true)->count(),
            'current' => RetirementAgeIncreaseFactor::where('is_active', true)
                ->whereDate('effective_from', '<=', today())
                ->where(function ($q): void {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today());
                })->count(),
            'future' => RetirementAgeIncreaseFactor::where('is_active', true)->whereDate('effective_from', '>', today())->count(),
        ];

        return view('pensions-administration.settings.retirement-increases.index', compact('factors', 'summary'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.retirement-increases.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateFactor($request);

        $this->ensureNoDateOverlap(
            ageYears: (int) $validated['age_years'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null
        );

        try {
            DB::transaction(function () use ($request, $validated): void {
                $factor = RetirementAgeIncreaseFactor::create([
                    'age_years' => $validated['age_years'],
                    'increase_percentage' => $validated['increase_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'retirement_increase_factor_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Retirement increase factor created.',
                    auditable: $factor,
                    oldValues: null,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'age_years' => (int) $validated['age_years'],
                        'increase_percentage' => $validated['increase_percentage'],
                        'effective_from' => $validated['effective_from'],
                        'effective_to' => $validated['effective_to'] ?? null,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('success', 'Retirement increase factor created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'retirement_increase_factor_create_failed',
                action: 'create',
                description: 'Failed to create retirement increase factor.',
                exception: $exception,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to create the retirement increase factor.');
        }
    }

    public function edit(RetirementAgeIncreaseFactor $factor): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'Inactive factors cannot be edited.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'This factor has already taken effect. Create a new version instead.');
        }

        return view('pensions-administration.settings.retirement-increases.edit', compact('factor'));
    }

    public function update(Request $request, RetirementAgeIncreaseFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'Inactive factors cannot be edited.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'This factor has already taken effect. Create a new version instead.');
        }

        $validated = $this->validateFactor($request);

        $this->ensureNoDateOverlap(
            ageYears: (int) $validated['age_years'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null,
            ignoreId: $factor->id
        );

        $oldValues = $this->auditService->values($factor);

        try {
            DB::transaction(function () use ($request, $factor, $validated, $oldValues): void {
                $factor->update([
                    'age_years' => $validated['age_years'],
                    'increase_percentage' => $validated['increase_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $factor->refresh();

                $this->auditService->log(
                    eventType: 'retirement_increase_factor_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Future retirement increase factor updated.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('success', 'Retirement increase factor updated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'retirement_increase_factor_update_failed',
                action: 'update',
                description: 'Failed to update retirement increase factor.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to update the retirement increase factor.');
        }
    }

    public function createVersion(RetirementAgeIncreaseFactor $factor): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'Inactive factors cannot be versioned.');
        }

        if (!$this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.edit', $factor);
        }

        if ($factor->effective_to) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'Only the current open-ended factor can be versioned.');
        }

        if (!$this->isLatestVersion($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'A later version already exists for this retirement age.');
        }

        return view('pensions-administration.settings.retirement-increases.version', compact('factor'));
    }

    public function storeVersion(Request $request, RetirementAgeIncreaseFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active || !$this->hasTakenEffect($factor) || $factor->effective_to || !$this->isLatestVersion($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'This retirement increase factor cannot be versioned.');
        }

        $validated = $request->validate([
            'increase_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $newEffectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        if ($newEffectiveFrom->lt(today())) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new version effective date cannot be earlier than today.',
            ]);
        }

        if ($newEffectiveFrom->lte($factor->effective_from->copy()->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new effective date must be after the current factor effective date.',
            ]);
        }

        $laterVersionExists = RetirementAgeIncreaseFactor::query()
            ->where('age_years', $factor->age_years)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $factor->effective_from)
            ->exists();

        if ($laterVersionExists) {
            throw ValidationException::withMessages([
                'effective_from' => 'A later active version already exists for this retirement age.',
            ]);
        }

        $oldValues = $this->auditService->values($factor);

        try {
            DB::transaction(function () use ($request, $factor, $validated, $newEffectiveFrom, $oldValues): void {
                $factor->update([
                    'effective_to' => $newEffectiveFrom->copy()->subDay()->toDateString(),
                    'updated_by' => auth()->id(),
                ]);

                $factor->refresh();

                $this->auditService->log(
                    eventType: 'retirement_increase_factor_version_closed',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'Previous retirement increase factor version closed.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'new_version_effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                $newFactor = RetirementAgeIncreaseFactor::create([
                    'age_years' => $factor->age_years,
                    'increase_percentage' => $validated['increase_percentage'],
                    'effective_from' => $newEffectiveFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? $factor->source_authority,
                    'notes' => $validated['notes'] ?? $factor->notes,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'retirement_increase_factor_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'New retirement increase factor version created.',
                    auditable: $newFactor,
                    oldValues: null,
                    newValues: $this->auditService->values($newFactor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'previous_version_id' => $factor->id,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('success', 'New retirement increase factor version created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'retirement_increase_factor_version_failed',
                action: 'version',
                description: 'Failed to create retirement increase factor version.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to create the new retirement increase factor version.');
        }
    }

    public function deactivate(Request $request, RetirementAgeIncreaseFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'This factor is already inactive.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('error', 'A factor that has already taken effect cannot be deactivated.');
        }

        $validated = $request->validate([
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $oldValues = $this->auditService->values($factor);

        try {
            DB::transaction(function () use ($request, $factor, $validated, $oldValues): void {
                $factor->update([
                    'is_active' => false,
                    'updated_by' => auth()->id(),
                ]);

                $factor->refresh();

                $this->auditService->log(
                    eventType: 'retirement_increase_factor_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: 'Future retirement increase factor deactivated.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.retirement-increases.index')->with('success', 'Retirement increase factor deactivated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'retirement_increase_factor_deactivate_failed',
                action: 'deactivate',
                description: 'Failed to deactivate retirement increase factor.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->with('error', 'Unable to deactivate the retirement increase factor.');
        }
    }

    private function validateFactor(Request $request): array
    {
        return $request->validate([
            'age_years' => ['required', 'integer', 'min:0', 'max:150'],
            'increase_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function ensureNoDateOverlap(int $ageYears, string $effectiveFrom, ?string $effectiveTo = null, ?int $ignoreId = null): void
    {
        $newFrom = Carbon::parse($effectiveFrom)->toDateString();
        $newTo = $effectiveTo ? Carbon::parse($effectiveTo)->toDateString() : null;

        $query = RetirementAgeIncreaseFactor::query()
            ->where('age_years', $ageYears)
            ->where('is_active', true);

        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);
        if ($newTo !== null) $query->whereDate('effective_from', '<=', $newTo);

        $query->where(function ($q) use ($newFrom): void {
            $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $newFrom);
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'An active retirement increase factor for this age already overlaps the selected effective period.',
            ]);
        }
    }

    private function isLatestVersion(RetirementAgeIncreaseFactor $factor): bool
    {
        return !RetirementAgeIncreaseFactor::query()
            ->where('age_years', $factor->age_years)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $factor->effective_from)
            ->exists();
    }

    private function hasTakenEffect(RetirementAgeIncreaseFactor $factor): bool
    {
        return $factor->effective_from?->copy()->startOfDay()->lte(today()) ?? false;
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

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?RetirementAgeIncreaseFactor $factor = null, ?array $metadata = null): void
    {
        try {
            $this->auditService->record(
                eventType: $eventType,
                module: 'pensions-benefit-settings',
                action: $action,
                description: $description,
                subject: $factor,
                oldValues: $factor ? $this->auditService->values($factor) : null,
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