<?php

namespace App\Http\Controllers\PensionsAdministration\Settings;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Settings\BenefitSetting;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class BenefitSettingController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }

    public function index(Request $request): View
    {
        $this->ensureViewPermission();

        $query = BenefitSetting::query()
            ->orderBy('category')
            ->orderBy('setting_key')
            ->orderByDesc('effective_from');

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($q) use ($search): void {
                $q->where('setting_key', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('source_authority', 'like', "%{$search}%");
            });
        }

        $settings = $query->paginate(50)->withQueryString();

        $categories = BenefitSetting::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view(
            'pensions-administration.settings.general.index',
            compact('settings', 'categories')
        );
    }

    public function create(): View
    {
        $this->ensureManagePermission();

        return view('pensions-administration.settings.general.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureManagePermission();

        $validated = $this->validateSetting($request);

        $this->validateValueForType($validated);
        $this->ensureEffectiveRangeIsValid($validated);
        $this->ensureNoOverlappingVersion(
            settingKey: $validated['setting_key'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null
        );

        try {
            $setting = DB::transaction(function () use ($validated, $request): BenefitSetting {
                $setting = BenefitSetting::create(
                    $this->settingPayload($validated) + [
                        'created_by' => auth()->id(),
                        'updated_by' => auth()->id(),
                    ]
                );

                $this->auditService->log(
                    eventType: 'benefit_setting_created',
                    module: 'pensions-benefit-settings',
                    action: 'create',
                    description: "Created pension benefit setting {$setting->setting_key}.",
                    auditable: $setting,
                    oldValues: null,
                    newValues: $this->auditService->values($setting),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'setting_key' => $setting->setting_key,
                        'effective_from' => $setting->effective_from?->format('Y-m-d'),
                        'effective_to' => $setting->effective_to?->format('Y-m-d'),
                    ],
                    request: $request
                );

                return $setting;
            });

            return redirect()
                ->route('pensions-administration.settings.general.index')
                ->with(
                    'success',
                    "Benefit rule {$setting->name} created successfully."
                );
        } catch (Throwable $exception) {
            report($exception);

            $this->auditService->failure(
                eventType: 'benefit_setting_create_failed',
                module: 'pensions-benefit-settings',
                action: 'create',
                description: 'Failed to create pension benefit setting.',
                failureReason: $exception->getMessage(),
                metadata: [
                    'setting_key' => $validated['setting_key'] ?? null,
                    'change_reason' => $validated['change_reason'] ?? null,
                ],
                request: $request
            );

            return back()
                ->withInput()
                ->with('error', 'The benefit rule could not be created.');
        }
    }

    public function edit(BenefitSetting $setting): View
    {
        $this->ensureManagePermission();

        abort_if(
            $this->hasAlreadyTakenEffect($setting),
            403,
            'This rule has already taken effect and cannot be edited directly. Use Add New Version.'
        );

        return view(
            'pensions-administration.settings.general.edit',
            compact('setting')
        );
    }

    public function update(
        Request $request,
        BenefitSetting $setting
    ): RedirectResponse {
        $this->ensureManagePermission();

        if ($this->hasAlreadyTakenEffect($setting)) {
            return back()->with(
                'error',
                'This rule has already taken effect. Use Add New Version instead of editing the historical record.'
            );
        }

        $validated = $this->validateSetting(
            request: $request,
            setting: $setting
        );

        $this->validateValueForType($validated);
        $this->ensureEffectiveRangeIsValid($validated);
        $this->ensureNoOverlappingVersion(
            settingKey: $validated['setting_key'],
            effectiveFrom: $validated['effective_from'],
            effectiveTo: $validated['effective_to'] ?? null,
            ignoreId: $setting->id
        );

        $oldValues = $this->auditService->values($setting);

        try {
            DB::transaction(function () use (
                $setting,
                $validated,
                $oldValues,
                $request
            ): void {
                $setting->update(
                    $this->settingPayload($validated) + [
                        'updated_by' => auth()->id(),
                    ]
                );

                $setting->refresh();

                $this->auditService->log(
                    eventType: 'benefit_setting_updated',
                    module: 'pensions-benefit-settings',
                    action: 'update',
                    description: "Updated future pension benefit setting {$setting->setting_key}.",
                    auditable: $setting,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($setting),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'setting_key' => $setting->setting_key,
                        'effective_from' => $setting->effective_from?->format('Y-m-d'),
                        'effective_to' => $setting->effective_to?->format('Y-m-d'),
                    ],
                    request: $request
                );
            });

            return redirect()
                ->route('pensions-administration.settings.general.index')
                ->with('success', 'Future benefit rule updated successfully.');
        } catch (Throwable $exception) {
            report($exception);

            $this->auditService->failure(
                eventType: 'benefit_setting_update_failed',
                module: 'pensions-benefit-settings',
                action: 'update',
                description: 'Failed to update pension benefit setting.',
                failureReason: $exception->getMessage(),
                auditable: $setting,
                metadata: [
                    'change_reason' => $validated['change_reason'] ?? null,
                ],
                request: $request
            );

            return back()
                ->withInput()
                ->with('error', 'The benefit rule could not be updated.');
        }
    }

    public function createVersion(BenefitSetting $setting): View
    {
        $this->ensureManagePermission();

        return view(
            'pensions-administration.settings.general.version',
            compact('setting')
        );
    }

    public function storeVersion(
        Request $request,
        BenefitSetting $setting
    ): RedirectResponse {
        $this->ensureManagePermission();

        $validated = $this->validateVersion($request, $setting);

        $this->validateValueForType($validated);

        $newEffectiveFrom = Carbon::parse($validated['effective_from'])->startOfDay();

        if ($newEffectiveFrom->lte(Carbon::parse($setting->effective_from)->startOfDay())) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new version must start after the previous version effective date.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | A version cannot be inserted inside a closed historical version.
        |--------------------------------------------------------------------------
        */

        if (
            $setting->effective_to
            &&
            $newEffectiveFrom->gt(Carbon::parse($setting->effective_to)->startOfDay())
        ) {
            throw ValidationException::withMessages([
                'effective_from' => 'The new version effective date falls outside the selected version period.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Ensure another version does not already start on/after this date.
        |--------------------------------------------------------------------------
        */

        $laterVersionExists = BenefitSetting::query()
            ->where('setting_key', $setting->setting_key)
            ->where('id', '<>', $setting->id)
            ->whereDate('effective_from', '>=', $newEffectiveFrom->toDateString())
            ->exists();

        if ($laterVersionExists) {
            throw ValidationException::withMessages([
                'effective_from' => 'Another version already exists on or after this effective date. Review the version history first.',
            ]);
        }

        $previousOldValues = $this->auditService->values($setting);

        try {
            $newSetting = DB::transaction(function () use (
                $setting,
                $validated,
                $newEffectiveFrom,
                $previousOldValues,
                $request
            ): BenefitSetting {
                $previousEffectiveTo = $newEffectiveFrom->copy()->subDay();

                $setting->update([
                    'effective_to' => $previousEffectiveTo->toDateString(),
                    'updated_by' => auth()->id(),
                ]);

                $setting->refresh();

                $newSetting = BenefitSetting::create([
                    'category' => $setting->category,
                    'setting_key' => $setting->setting_key,
                    'name' => $validated['name'],
                    'description' => $validated['description'] ?? null,
                    'value_type' => $setting->value_type,

                    'value_decimal' => $setting->value_type === 'decimal'
                        ? $validated['value_decimal']
                        : null,

                    'value_integer' => $setting->value_type === 'integer'
                        ? $validated['value_integer']
                        : null,

                    'value_string' => $setting->value_type === 'string'
                        ? $validated['value_string']
                        : null,

                    'value_boolean' => $setting->value_type === 'boolean'
                        ? $validated['value_boolean']
                        : null,

                    'effective_from' => $newEffectiveFrom->toDateString(),
                    'effective_to' => null,
                    'source_authority' => $validated['source_authority'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);

                $this->auditService->log(
                    eventType: 'benefit_setting_version_closed',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: "Closed previous version of pension benefit setting {$setting->setting_key}.",
                    auditable: $setting,
                    oldValues: $previousOldValues,
                    newValues: $this->auditService->values($setting),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'new_version_id' => $newSetting->id,
                        'new_effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                $this->auditService->log(
                    eventType: 'benefit_setting_version_created',
                    module: 'pensions-benefit-settings',
                    action: 'version',
                    description: "Created new version of pension benefit setting {$setting->setting_key}.",
                    auditable: $newSetting,
                    oldValues: $previousOldValues,
                    newValues: $this->auditService->values($newSetting),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'previous_version_id' => $setting->id,
                        'new_version_id' => $newSetting->id,
                        'effective_from' => $newEffectiveFrom->toDateString(),
                    ],
                    request: $request
                );

                return $newSetting;
            });

            return redirect()
                ->route('pensions-administration.settings.general.index')
                ->with(
                    'success',
                    "New version of {$newSetting->name} created successfully."
                );
        } catch (Throwable $exception) {
            report($exception);

            $this->auditService->failure(
                eventType: 'benefit_setting_version_failed',
                module: 'pensions-benefit-settings',
                action: 'version',
                description: "Failed to create a new version of {$setting->setting_key}.",
                failureReason: $exception->getMessage(),
                auditable: $setting,
                metadata: [
                    'change_reason' => $validated['change_reason'] ?? null,
                    'requested_effective_from' => $validated['effective_from'] ?? null,
                ],
                request: $request
            );

            return back()
                ->withInput()
                ->with('error', 'The new benefit rule version could not be created.');
        }
    }

    public function deactivate(
        Request $request,
        BenefitSetting $setting
    ): RedirectResponse {
        $this->ensureManagePermission();

        $validated = $request->validate([
            'change_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);

        if ($this->hasAlreadyTakenEffect($setting)) {
            return back()->with(
                'error',
                'An already-effective rule cannot be deactivated destructively. Create a new version or close its effective period instead.'
            );
        }

        if (!$setting->is_active) {
            return back()->with(
                'error',
                'This benefit rule is already inactive.'
            );
        }

        $oldValues = $this->auditService->values($setting);

        try {
            DB::transaction(function () use (
                $setting,
                $validated,
                $oldValues,
                $request
            ): void {
                $setting->update([
                    'is_active' => false,
                    'updated_by' => auth()->id(),
                ]);

                $setting->refresh();

                $this->auditService->log(
                    eventType: 'benefit_setting_deactivated',
                    module: 'pensions-benefit-settings',
                    action: 'deactivate',
                    description: "Deactivated future pension benefit setting {$setting->setting_key}.",
                    auditable: $setting,
                    oldValues: $oldValues,
                    newValues: $this->auditService->values($setting),
                    metadata: [
                        'change_reason' => $validated['change_reason'],
                        'effective_from' => $setting->effective_from?->format('Y-m-d'),
                    ],
                    request: $request
                );
            });

            return back()->with(
                'success',
                'Future benefit rule deactivated successfully.'
            );
        } catch (Throwable $exception) {
            report($exception);

            $this->auditService->failure(
                eventType: 'benefit_setting_deactivate_failed',
                module: 'pensions-benefit-settings',
                action: 'deactivate',
                description: "Failed to deactivate {$setting->setting_key}.",
                failureReason: $exception->getMessage(),
                auditable: $setting,
                metadata: [
                    'change_reason' => $validated['change_reason'],
                ],
                request: $request
            );

            return back()->with(
                'error',
                'The benefit rule could not be deactivated.'
            );
        }
    }

    private function validateSetting(
        Request $request,
        ?BenefitSetting $setting = null
    ): array {
        return $request->validate([
            'category' => [
                'required',
                'string',
                'max:100',
            ],

            'setting_key' => [
                'required',
                'string',
                'max:180',
                'regex:/^[a-z0-9._-]+$/',
            ],

            'name' => [
                'required',
                'string',
                'max:180',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'value_type' => [
                'required',
                Rule::in([
                    'decimal',
                    'integer',
                    'string',
                    'boolean',
                ]),
            ],

            'value_decimal' => [
                'nullable',
                'numeric',
            ],

            'value_integer' => [
                'nullable',
                'integer',
            ],

            'value_string' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'value_boolean' => [
                'nullable',
                Rule::in(['0', '1', 0, 1, true, false]),
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'effective_to' => [
                'nullable',
                'date',
                'after_or_equal:effective_from',
            ],

            'source_authority' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'change_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ]);
    }

    private function validateVersion(
        Request $request,
        BenefitSetting $setting
    ): array {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:180',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'effective_from' => [
                'required',
                'date',
            ],

            'source_authority' => [
                'nullable',
                'string',
                'max:255',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'change_reason' => [
                'required',
                'string',
                'min:5',
                'max:2000',
            ],
        ];

        if ($setting->value_type === 'decimal') {
            $rules['value_decimal'] = [
                'required',
                'numeric',
            ];
        }

        if ($setting->value_type === 'integer') {
            $rules['value_integer'] = [
                'required',
                'integer',
            ];
        }

        if ($setting->value_type === 'string') {
            $rules['value_string'] = [
                'required',
                'string',
                'max:1000',
            ];
        }

        if ($setting->value_type === 'boolean') {
            $rules['value_boolean'] = [
                'required',
                Rule::in(['0', '1', 0, 1, true, false]),
            ];
        }

        $validated = $request->validate($rules);

        $validated['value_type'] = $setting->value_type;

        return $validated;
    }

    private function validateValueForType(array $validated): void
    {
        $valueType = $validated['value_type'];

        $field = match ($valueType) {
            'decimal' => 'value_decimal',
            'integer' => 'value_integer',
            'string' => 'value_string',
            'boolean' => 'value_boolean',
            default => null,
        };

        if (
            !$field
            ||
            !array_key_exists($field, $validated)
            ||
            $validated[$field] === null
            ||
            $validated[$field] === ''
        ) {
            throw ValidationException::withMessages([
                $field ?? 'value_type' => 'A value is required for the selected value type.',
            ]);
        }
    }

    private function ensureEffectiveRangeIsValid(array $validated): void
    {
        if (empty($validated['effective_to'])) {
            return;
        }

        $from = Carbon::parse($validated['effective_from'])->startOfDay();
        $to = Carbon::parse($validated['effective_to'])->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'effective_to' => 'Effective To cannot be earlier than Effective From.',
            ]);
        }
    }

    private function ensureNoOverlappingVersion(
        string $settingKey,
        string $effectiveFrom,
        ?string $effectiveTo = null,
        ?int $ignoreId = null
    ): void {
        $newFrom = Carbon::parse($effectiveFrom)->toDateString();
        $newTo = $effectiveTo
            ? Carbon::parse($effectiveTo)->toDateString()
            : null;

        $query = BenefitSetting::query()
            ->where('setting_key', $settingKey)
            ->where('is_active', true);

        if ($ignoreId !== null) {
            $query->where('id', '<>', $ignoreId);
        }

        $query->where(function ($q) use ($newFrom, $newTo): void {
            $q->where(function ($existing) use ($newFrom, $newTo): void {
                $existing
                    ->where(function ($start) use ($newTo): void {
                        if ($newTo === null) {
                            return;
                        }

                        $start->whereDate('effective_from', '<=', $newTo);
                    })
                    ->where(function ($end) use ($newFrom): void {
                        $end->whereNull('effective_to')
                            ->orWhereDate('effective_to', '>=', $newFrom);
                    });
            });

            if ($newTo === null) {
                $q->orWhere(function ($openEnded) use ($newFrom): void {
                    $openEnded
                        ->whereNull('effective_to')
                        ->orWhereDate('effective_to', '>=', $newFrom);
                });
            }
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'effective_from' => 'This effective period overlaps another active version of the same setting.',
            ]);
        }
    }

    private function settingPayload(array $validated): array
    {
        return [
            'category' => trim($validated['category']),
            'setting_key' => strtolower(trim($validated['setting_key'])),
            'name' => trim($validated['name']),
            'description' => $validated['description'] ?? null,
            'value_type' => $validated['value_type'],

            'value_decimal' => $validated['value_type'] === 'decimal'
                ? $validated['value_decimal']
                : null,

            'value_integer' => $validated['value_type'] === 'integer'
                ? $validated['value_integer']
                : null,

            'value_string' => $validated['value_type'] === 'string'
                ? $validated['value_string']
                : null,

            'value_boolean' => $validated['value_type'] === 'boolean'
                ? (bool) $validated['value_boolean']
                : null,

            'effective_from' => $validated['effective_from'],
            'effective_to' => $validated['effective_to'] ?? null,
            'source_authority' => $validated['source_authority'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => true,
        ];
    }

    private function hasAlreadyTakenEffect(BenefitSetting $setting): bool
    {
        return $setting->effective_from
            ->startOfDay()
            ->lte(today());
    }

    private function ensureViewPermission(): void
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->hasRole('system-administrator')
            &&
            auth()->user()->can('pensions.settings.view'),
            403
        );
    }

    private function ensureManagePermission(): void
    {
        abort_unless(
            auth()->check()
            &&
            auth()->user()->hasRole('system-administrator')
            &&
            auth()->user()->can('pensions.settings.manage'),
            403
        );
    }
}