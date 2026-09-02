<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\WithdrawalEmployerEntitlementScale;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class WithdrawalEntitlementScaleController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(Request $request): View
    {
        $query = WithdrawalEmployerEntitlementScale::query()->orderByDesc('effective_from')->orderBy('minimum_service_months');

        if ($request->filled('status')) {
            if ($request->status === 'active') $query->where('is_active', true);
            if ($request->status === 'inactive') $query->where('is_active', false);
        }

        $scales = $query->paginate(50)->withQueryString();

        return view('pensions-administration.settings.withdrawal-scales.index', compact('scales'));
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.withdrawal-scales.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateScale($request);
        $this->validateServiceRange($validated);
        $this->ensureNoOverlap($validated);

        try {
            DB::transaction(function () use ($request, $validated): void {
                $scale = WithdrawalEmployerEntitlementScale::create([
                    'minimum_service_months' => $validated['minimum_service_months'],
                    'maximum_service_months' => $validated['maximum_service_months'] ?? null,
                    'entitlement_percentage' => $validated['entitlement_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'withdrawal_scale_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: 'Withdrawal employer entitlement scale created.',
                    auditable: $scale,
                    oldValues: null,
                    newValues: $this->auditService->values($scale),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'effective_from' => $validated['effective_from'],
                        'effective_to' => $validated['effective_to'] ?? null,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('success', 'Withdrawal entitlement scale created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'withdrawal_scale_create_failed', 'create', 'Failed to create withdrawal entitlement scale.', $exception, null, $validated);

            return back()->withInput()->with('error', 'Unable to create the withdrawal entitlement scale.');
        }
    }

    public function edit(WithdrawalEmployerEntitlementScale $scale): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$scale->is_active) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'Inactive scales cannot be edited.');

        if ($this->hasTakenEffect($scale)) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'This scale has already taken effect. Create a new version instead.');

        return view('pensions-administration.settings.withdrawal-scales.edit', compact('scale'));
    }

    public function update(Request $request, WithdrawalEmployerEntitlementScale $scale): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$scale->is_active) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'Inactive scales cannot be edited.');

        if ($this->hasTakenEffect($scale)) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'This scale has already taken effect. Create a new version instead.');

        $validated = $this->validateScale($request);
        $this->validateServiceRange($validated);
        $this->ensureNoOverlap($validated, $scale->id);

        $oldValues = $this->auditService->values($scale);

        try {
            DB::transaction(function () use ($request, $validated, $scale, $oldValues): void {
                $scale->update([
                    'minimum_service_months' => $validated['minimum_service_months'],
                    'maximum_service_months' => $validated['maximum_service_months'] ?? null,
                    'entitlement_percentage' => $validated['entitlement_percentage'],
                    'effective_from' => $validated['effective_from'],
                    'effective_to' => $validated['effective_to'] ?? null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'updated_by' => auth()->id(),
                ]);

                $scale->refresh();

                $this->auditService->log(
                    eventType: 'withdrawal_scale_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: 'Future withdrawal employer entitlement scale updated.',
                    auditable: $scale,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($scale),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('success', 'Withdrawal entitlement scale updated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'withdrawal_scale_update_failed', 'update', 'Failed to update withdrawal entitlement scale.', $exception, $scale, $validated);

            return back()->withInput()->with('error', 'Unable to update the withdrawal entitlement scale.');
        }
    }

    public function createVersion(WithdrawalEmployerEntitlementScale $scale): View|RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$scale->is_active) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'Inactive scales cannot be versioned.');

        if (!$this->hasTakenEffect($scale)) return redirect()->route('pensions-administration.settings.withdrawal-scales.edit', $scale);

        if ($scale->effective_to) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'Only the current open-ended version can be versioned.');

        return view('pensions-administration.settings.withdrawal-scales.version', compact('scale'));
    }

    public function storeVersion(Request $request, WithdrawalEmployerEntitlementScale $scale): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$scale->is_active || !$this->hasTakenEffect($scale) || $scale->effective_to) {
            return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'This scale cannot be versioned.');
        }

        $validated = $request->validate([
            'entitlement_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);

        $newEffectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        if ($newEffectiveFrom->lt(today())) {
            throw ValidationException::withMessages(['effective_from' => 'The new version effective date cannot be earlier than today.']);
        }

        if ($newEffectiveFrom->lte($scale->effective_from->copy()->startOfDay())) {
            throw ValidationException::withMessages(['effective_from' => 'The new effective date must be after the current version effective date.']);
        }

        $laterVersionExists = WithdrawalEmployerEntitlementScale::query()
            ->where('minimum_service_months', $scale->minimum_service_months)
            ->where('is_active', true)
            ->whereDate('effective_from', '>', $scale->effective_from)
            ->exists();

        if ($laterVersionExists) {
            throw ValidationException::withMessages(['effective_from' => 'A later version already exists for this service band.']);
        }

        $oldValues = $this->auditService->values($scale);

        try {
            DB::transaction(function () use ($request, $scale, $validated, $newEffectiveFrom, $oldValues): void {
                $scale->update([
                    'effective_to' => $newEffectiveFrom->copy()->subDay()->toDateString(),
                    'updated_by' => auth()->id(),
                ]);

                $scale->refresh();

                $this->auditService->log(
                    eventType: 'withdrawal_scale_version_closed',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'Previous withdrawal entitlement scale version closed.',
                    auditable: $scale,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($scale),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'new_version_effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                $newScale = WithdrawalEmployerEntitlementScale::create([
                    'minimum_service_months' => $scale->minimum_service_months,
                    'maximum_service_months' => $scale->maximum_service_months,
                    'entitlement_percentage' => $validated['entitlement_percentage'],
                    'effective_from' => $newEffectiveFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? $scale->source_authority,
                    'notes' => $validated['notes'] ?? $scale->notes,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'withdrawal_scale_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: 'New withdrawal entitlement scale version created.',
                    auditable: $newScale,
                    oldValues: null,
                    newValues: $this->auditService->values($newScale),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'previous_version_id' => $scale->id,
                    ],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('success', 'New withdrawal entitlement scale version created successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'withdrawal_scale_version_failed', 'version', 'Failed to create withdrawal entitlement scale version.', $exception, $scale, $validated);

            return back()->withInput()->with('error', 'Unable to create the new scale version.');
        }
    }

    public function deactivate(Request $request, WithdrawalEmployerEntitlementScale $scale): RedirectResponse
    {
        $this->ensureManagePermission();

        if (!$scale->is_active) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'This scale is already inactive.');

        if ($this->hasTakenEffect($scale)) return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('error', 'A scale that has already taken effect cannot be deactivated.');

        $validated = $request->validate(['change_reason' => ['required', 'string', 'max:2000']]);
        $oldValues = $this->auditService->values($scale);

        try {
            DB::transaction(function () use ($request, $scale, $validated, $oldValues): void {
                $scale->update(['is_active' => false, 'updated_by' => auth()->id()]);
                $scale->refresh();

                $this->auditService->log(
                    eventType: 'withdrawal_scale_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: 'Future withdrawal entitlement scale deactivated.',
                    auditable: $scale,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($scale),
                    metadata: ['change_reason' => $validated['change_reason']],
                    request: $request
                );
            });

            return redirect()->route('pensions-administration.settings.withdrawal-scales.index')->with('success', 'Withdrawal entitlement scale deactivated successfully.');
        } catch (Throwable $exception) {
            $this->recordFailure($request, 'withdrawal_scale_deactivate_failed', 'deactivate', 'Failed to deactivate withdrawal entitlement scale.', $exception, $scale, $validated);

            return back()->with('error', 'Unable to deactivate the withdrawal entitlement scale.');
        }
    }

    private function validateScale(Request $request): array
    {
        return $request->validate([
            'minimum_service_months' => ['required', 'integer', 'min:0'],
            'maximum_service_months' => ['nullable', 'integer', 'min:0'],
            'entitlement_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'source_authority' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:2000'],
        ]);
    }

    private function validateServiceRange(array $validated): void
    {
        if (isset($validated['maximum_service_months']) && $validated['maximum_service_months'] !== null && (int) $validated['maximum_service_months'] < (int) $validated['minimum_service_months']) {
            throw ValidationException::withMessages(['maximum_service_months' => 'Maximum service months cannot be less than minimum service months.']);
        }
    }

    private function ensureNoOverlap(array $validated, ?int $ignoreId = null): void
    {
        $newMin = (int) $validated['minimum_service_months'];
        $newMax = isset($validated['maximum_service_months']) && $validated['maximum_service_months'] !== null ? (int) $validated['maximum_service_months'] : null;
        $newFrom = Carbon::parse($validated['effective_from'])->toDateString();
        $newTo = !empty($validated['effective_to']) ? Carbon::parse($validated['effective_to'])->toDateString() : null;

        $query = WithdrawalEmployerEntitlementScale::query()->where('is_active', true);

        if ($ignoreId !== null) $query->whereKeyNot($ignoreId);

        $query->where(function ($dateQuery) use ($newFrom, $newTo): void {
            if ($newTo !== null) $dateQuery->whereDate('effective_from', '<=', $newTo);

            $dateQuery->where(function ($endQuery) use ($newFrom): void {
                $endQuery->whereNull('effective_to')->orWhereDate('effective_to', '>=', $newFrom);
            });
        });

        $query->where(function ($serviceQuery) use ($newMin, $newMax): void {
            if ($newMax !== null) $serviceQuery->where('minimum_service_months', '<=', $newMax);

            $serviceQuery->where(function ($maxQuery) use ($newMin): void {
                $maxQuery->whereNull('maximum_service_months')->orWhere('maximum_service_months', '>=', $newMin);
            });
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'minimum_service_months' => 'This service range overlaps another active withdrawal entitlement scale during the selected effective period.',
            ]);
        }
    }

    private function hasTakenEffect(WithdrawalEmployerEntitlementScale $scale): bool
    {
        return $scale->effective_from?->copy()->startOfDay()->lte(today()) ?? false;
    }

    private function ensureManagePermission(): void
    {
        abort_unless(auth()->user()?->hasRole('system-administrator') && auth()->user()?->can('pensions.settings.manage'), 403);
    }

    private function recordFailure(Request $request, string $eventType, string $action, string $description, Throwable $exception, ?WithdrawalEmployerEntitlementScale $scale = null, ?array $metadata = null): void
    {
        try {
            $this->auditService->record(
                eventType: $eventType,
                module: 'pensions-benefit-settings',
                action: $action,
                description: $description,
                subject: $scale,
                oldValues: $scale ? $this->auditService->values($scale) : null,
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