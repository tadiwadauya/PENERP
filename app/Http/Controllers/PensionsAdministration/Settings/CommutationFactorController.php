<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\CommutationFactor;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class CommutationFactorController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(Request $request): View
    {
        $query = CommutationFactor::query()
            ->orderBy('age_years')
            ->orderBy('age_months')
            ->orderBy('gender')
            ->orderByDesc('effective_from');

        if ($request->filled('gender')) $query->where('gender', strtolower($request->gender));
        if ($request->filled('age_from')) $query->where('age_years', '>=', (int) $request->age_from);
        if ($request->filled('age_to')) $query->where('age_years', '<=', (int) $request->age_to);
        if ($request->filled('age_months')) $query->where('age_months', (int) $request->age_months);

        if ($request->filled('status')) {
            if ($request->status === 'active') $query->where('is_active', true);
            if ($request->status === 'inactive') $query->where('is_active', false);

            if ($request->status === 'current') {
                $query->where('is_active', true)
                    ->whereDate('effective_from', '<=', today())
                    ->where(function ($q): void {
                        $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today());
                    });
            }

            if ($request->status === 'future') {
                $query->where('is_active', true)->whereDate('effective_from', '>', today());
            }

            if ($request->status === 'historical') {
                $query->whereNotNull('effective_to')->whereDate('effective_to', '<', today());
            }
        }

        $factors = $query->paginate(50)->withQueryString();

        $summary = [
            'total' => CommutationFactor::count(),
            'active' => CommutationFactor::where('is_active', true)->count(),
            'current' => CommutationFactor::where('is_active', true)
                ->whereDate('effective_from', '<=', today())
                ->where(function ($q): void {
                    $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', today());
                })->count(),
            'future' => CommutationFactor::where('is_active', true)->whereDate('effective_from', '>', today())->count(),
        ];

        return view('pensions-administration.settings.commutation-factors.index', compact('factors', 'summary'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.commutation-factors.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateFactor($request);
        $validated['gender'] = strtolower($validated['gender']);

        $this->ensureNoDateOverlap(
            ageYears: (int) $validated['age_years'],
            ageMonths: (int) $validated['age_months'],
            gender: $validated['gender'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null
        );

        try {
            DB::transaction(function () use ($request, $validated): void {
                $factor = CommutationFactor::create([
                    'age_years' => $validated['age_years'],
                    'age_months' => $validated['age_months'],
                    'gender' => $validated['gender'],
                    'factor' => $validated['factor'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'commutation_factor_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Commutation factor created.',
                    auditable: $factor,
                    oldValues: null,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'age_years' => (int) $validated['age_years'],
                        'age_months' => (int) $validated['age_months'],
                        'gender' => $validated['gender'],
                        'effective_from' => $validated['effective_from'],
                        'effective_to' => $validated['effective_to'] ?? null,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('success', 'Commutation factor created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'commutation_factor_create_failed',
                action: 'create',
                description: 'Failed to create commutation factor.',
                exception: $exception,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to create the commutation factor.');
        }
    }

    public function edit(CommutationFactor $factor): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'Inactive factors cannot be edited.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'This factor has already taken effect. Create a new version instead.');
        }

        return view('pensions-administration.settings.commutation-factors.edit', compact('factor'));
    }

    public function update(Request $request, CommutationFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'Inactive factors cannot be edited.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'This factor has already taken effect. Create a new version instead.');
        }

        $validated = $this->validateFactor($request);
        $validated['gender'] = strtolower($validated['gender']);

        $this->ensureNoDateOverlap(
            ageYears: (int) $validated['age_years'],
            ageMonths: (int) $validated['age_months'],
            gender: $validated['gender'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null,
            ignoreId: $factor->id
        );

        $oldValues = $this->auditService->values($factor);

        try {
            DB::transaction(function () use ($request, $factor, $validated, $oldValues): void {
                $factor->update([
                    'age_years' => $validated['age_years'],
                    'age_months' => $validated['age_months'],
                    'gender' => $validated['gender'],
                    'factor' => $validated['factor'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $factor->refresh();

                $this->auditService->log(
                    eventType: 'commutation_factor_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Future commutation factor updated.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('success', 'Commutation factor updated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'commutation_factor_update_failed',
                action: 'update',
                description: 'Failed to update commutation factor.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to update the commutation factor.');
        }
    }

    public function createVersion(CommutationFactor $factor): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'Inactive factors cannot be versioned.');
        }

        if (!$this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.edit', $factor);
        }

        if ($factor->effective_to) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'Only the current open-ended factor can be versioned.');
        }

        if (!$this->isLatestVersion($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'A later version already exists for this age, month and gender.');
        }

        return view('pensions-administration.settings.commutation-factors.version', compact('factor'));
    }

    public function storeVersion(Request $request, CommutationFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active || !$this->hasTakenEffect($factor) || $factor->effective_to || !$this->isLatestVersion($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'This factor cannot be versioned.');
        }

        $validated = $request->validate([
            'factor' => ['required', 'numeric', 'gt:0'],
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

        $laterVersionExists = CommutationFactor::query()
            ->where('age_years', $factor->age_years)
            ->where('age_months', $factor->age_months)
            ->where('gender', $factor->gender)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $factor->effective_from)
            ->exists();

        if ($laterVersionExists) {
            throw ValidationException::withMessages([
                'effective_from' => 'A later active version already exists for this age, month and gender.',
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
                    eventType: 'commutation_factor_version_closed',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'Previous commutation factor version closed.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'new_version_effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                $newFactor = CommutationFactor::create([
                    'age_years' => $factor->age_years,
                    'age_months' => $factor->age_months,
                    'gender' => $factor->gender,
                    'factor' => $validated['factor'],
                    'effective_from' => $newEffectiveFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? $factor->source_authority,
                    'notes' => $validated['notes'] ?? $factor->notes,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'commutation_factor_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'New commutation factor version created.',
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

            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('success', 'New commutation factor version created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'commutation_factor_version_failed',
                action: 'version',
                description: 'Failed to create commutation factor version.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->withInput()->with('error', 'Unable to create the new commutation factor version.');
        }
    }

    public function deactivate(Request $request, CommutationFactor $factor): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$factor->is_active) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'This factor is already inactive.');
        }

        if ($this->hasTakenEffect($factor)) {
            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('error', 'A factor that has already taken effect cannot be deactivated.');
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
                    eventType: 'commutation_factor_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: 'Future commutation factor deactivated.',
                    auditable: $factor,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($factor),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.commutation-factors.index')->with('success', 'Commutation factor deactivated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure(
                request: $request,
                eventType: 'commutation_factor_deactivate_failed',
                action: 'deactivate',
                description: 'Failed to deactivate commutation factor.',
                exception: $exception,
                factor: $factor,
                metadata: $validated
            );

            return back()->with('error', 'Unable to deactivate the commutation factor.');
        }
    }

    private function validateFactor(Request $request): array
    {
        return $request->validate([
            'age_years' => ['required', 'integer', 'min:0', 'max:150'],
            'age_months' => ['required', 'integer', 'min:0', 'max:11'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'factor' => ['required', 'numeric', 'gt:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function ensureNoDateOverlap(int $ageYears, int $ageMonths, string $gender, string $effectiveFrom, ?string $effectiveTo = null, ?int $ignoreId = null): void
    {
        $newFrom = Carbon::parse($effectiveFrom)->toDateString();
        $newTo = $effectiveTo ? Carbon::parse($effectiveTo)->toDateString() : null;

        $query = CommutationFactor::query()
            ->where('age_years', $ageYears)
            ->where('age_months', $ageMonths)
            ->where('gender', strtolower($gender))
            ->where('is_active', true);

        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);

        if ($newTo !== null) $query->whereDate('effective_from', '<=', $newTo);

        $query->where(function ($q) use ($newFrom): void {
            $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $newFrom);
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'An active commutation factor for this age, month and gender already overlaps the selected effective period.',
            ]);
        }
    }

    private function isLatestVersion(CommutationFactor $factor): bool
    {
        return !CommutationFactor::query()
            ->where('age_years', $factor->age_years)
            ->where('age_months', $factor->age_months)
            ->where('gender', $factor->gender)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $factor->effective_from)
            ->exists();
    }

    private function hasTakenEffect(CommutationFactor $factor): bool
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

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?CommutationFactor $factor = null, ?array $metadata = null): void
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