<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Models\PensionsAdministration\Updates\MembershipImportRow;
use App\Services\Audit\AuditService;
use App\Services\PensionsAdministration\Updates\MembershipImportRowValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipImportReviewController extends Controller
{
    public function __construct(
        private readonly MembershipImportRowValidationService $validationService,
        private readonly AuditService $auditService
    ) {
    }

    public function index(Request $request, MembershipImportBatch $batch): View
    {
        $query = MembershipImportRow::query()
            ->with([
                'matchedEmployer',
                'matchedMember',
            ])
            ->where('import_batch_id', $batch->id)
            ->orderBy('row_number');

        if ($request->filled('status')) {
            $query->where('validation_status', $request->input('status'));
        }

        if ($request->filled('duplicate')) {
            $query->where('duplicate_status', $request->input('duplicate'));
        }

        if ($request->filled('decision')) {
            $query->where('review_decision', $request->input('decision'));
        }

        $rows = $query
            ->paginate(50)
            ->withQueryString();

        $counts = [
            'total' => $batch->rows()->count(),

            'valid' => $batch->rows()
                ->where('validation_status', 'valid')
                ->count(),

            'warnings' => $batch->rows()
                ->where('validation_status', 'warning')
                ->count(),

            'errors' => $batch->rows()
                ->where('validation_status', 'error')
                ->count(),

            'duplicates' => $batch->rows()
                ->where('duplicate_status', '<>', 'none')
                ->count(),

            'approved' => $batch->rows()
                ->whereIn('review_decision', [
                    'create',
                    'update',
                    'use_existing',
                    'ignore_warning',
                ])
                ->count(),

            'rejected' => $batch->rows()
                ->where('review_decision', 'reject')
                ->count(),

            'pending' => $batch->rows()
                ->where('review_decision', 'pending')
                ->count(),
        ];

        return view(
            'pensions-administration.updates.imports.review',
            compact('batch', 'rows', 'counts')
        );
    }

    public function edit(
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): View {
        $this->ensureRowBelongsToBatch($batch, $row);

        $employers = Employer::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'pensions-administration.updates.imports.edit-row',
            compact('batch', 'row', 'employers')
        );
    }

    public function update(
        Request $request,
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): RedirectResponse {
        $this->ensureRowBelongsToBatch($batch, $row);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:50'],

            'surname' => ['nullable', 'string', 'max:150'],
            'first_names' => ['nullable', 'string', 'max:200'],
            'other_names' => ['nullable', 'string', 'max:200'],
            'maiden_name' => ['nullable', 'string', 'max:150'],

            'national_id' => ['nullable', 'string', 'max:100'],

            'penad_member_number' => ['nullable', 'string', 'max:100'],
            'fundworx_member_number' => ['nullable', 'string', 'max:100'],

            'penerp_employer_number' => ['nullable', 'string', 'max:100'],
            'penad_employer_number' => ['nullable', 'string', 'max:100'],
            'fundworx_employer_number' => ['nullable', 'string', 'max:100'],

            'membership_status' => [
                'required',
                'in:active,dormant,inactive,suspended',
            ],

            'staff_number' => ['nullable', 'string', 'max:100'],
            'vote_number' => ['nullable', 'string', 'max:100'],

            'date_of_birth' => ['nullable', 'date'],
            'date_joined_fund' => ['nullable', 'date'],
            'date_joined_employer' => ['nullable', 'date'],

            'gender' => ['nullable', 'string', 'max:30'],
            'marital_status' => ['nullable', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:150'],

            'email' => ['nullable', 'email', 'max:150'],
            'secondary_email' => ['nullable', 'email', 'max:150'],

            'cell_number' => ['nullable', 'string', 'max:100'],
            'secondary_cell_number' => ['nullable', 'string', 'max:100'],

            'department' => ['nullable', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:150'],

            'selected_employer_id' => [
                'nullable',
                'exists:employers,id',
            ],
        ]);

        $data = $row->normalized_data ?? [];

        foreach ($validated as $key => $value) {
            if ($key === 'selected_employer_id') {
                continue;
            }

            $data[$key] = $value;
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Selected Manually
        |--------------------------------------------------------------------------
        */

        if (!empty($validated['selected_employer_id'])) {
            $employer = Employer::findOrFail(
                $validated['selected_employer_id']
            );

            $data['penerp_employer_number'] = $employer->employer_number;
            $data['penad_employer_number'] = $employer->penad_employer_number;
            $data['fundworx_employer_number'] = $employer->fundworx_employer_number;
        }

        $oldValues = $this->auditService->values($row);

        $this->validationService->revalidate(
            $row,
            $data
        );

        $this->refreshBatchCounters($batch);

        $this->auditService->log(
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'CORRECT_IMPORT_ROW',
            description: 'Membership import Excel row '
                . $row->row_number
                . ' was corrected and revalidated.',
            auditable: $row,
            oldValues: $oldValues,
            newValues: $this->auditService->values($row),
            metadata: [
                'batch_id' => $batch->id,
                'excel_row' => $row->row_number,
            ],
            request: $request
        );

        return redirect()
            ->route(
                'pensions-administration.updates.imports.review',
                $batch
            )
            ->with(
                'success',
                'Excel row '
                . $row->row_number
                . ' was corrected and revalidated.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Review Decision
    |--------------------------------------------------------------------------
    */

    public function decision(
        Request $request,
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): RedirectResponse {
        $this->ensureRowBelongsToBatch($batch, $row);

        $validated = $request->validate([
            'decision' => [
                'required',
                'in:create,update,use_existing,ignore_warning,reject',
            ],

            'review_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        $decision = $validated['decision'];

        if (
            $row->validation_status === 'error'
            && $decision !== 'reject'
        ) {
            return back()->with(
                'error',
                'This row still contains validation errors. Correct the row first or remove it from the import.'
            );
        }

        if (
            in_array($decision, ['update', 'use_existing'], true)
            && !$row->matched_member_id
        ) {
            return back()->with(
                'error',
                'No existing member is linked to this row.'
            );
        }

        if (
            $decision === 'create'
            && $row->duplicate_status === 'exact'
            && empty($validated['review_notes'])
        ) {
            return back()->with(
                'error',
                'Please provide a review note explaining why the duplicate should be created as a separate member.'
            );
        }

        $oldValues = $this->auditService->values($row);

        $row->update([
            'review_decision' => $decision,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->refreshBatchCounters($batch);

        $this->auditService->log(
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'REVIEW_IMPORT_ROW',
            description: 'A review decision was recorded for membership import Excel row '
                . $row->row_number
                . '.',
            auditable: $row,
            oldValues: $oldValues,
            newValues: $this->auditService->values($row),
            metadata: [
                'batch_id' => $batch->id,
                'excel_row' => $row->row_number,
                'decision' => $decision,
            ],
            request: $request
        );

        return back()->with(
            'success',
            'Review decision saved for Excel row '
            . $row->row_number
            . '.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Remove Row From Import
    |--------------------------------------------------------------------------
    |
    | We do not physically delete the staging record.
    |
    | It is marked rejected so the original uploaded record remains
    | available for audit and investigation.
    |
    */

    public function removeRow(
        Request $request,
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): RedirectResponse {
        $this->ensureRowBelongsToBatch($batch, $row);

        if ($row->imported_at) {
            return back()->with(
                'error',
                'This row has already been imported and cannot be removed from the batch.'
            );
        }

        $oldValues = $this->auditService->values($row);

        $row->update([
            'review_decision' => 'reject',
            'review_notes' => 'Removed from the import during review.',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $this->refreshBatchCounters($batch);

        $this->auditService->log(
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'REMOVE_IMPORT_ROW',
            description: 'Membership import Excel row '
                . $row->row_number
                . ' was removed from the final import.',
            auditable: $row,
            oldValues: $oldValues,
            newValues: $this->auditService->values($row),
            metadata: [
                'batch_id' => $batch->id,
                'excel_row' => $row->row_number,
            ],
            request: $request
        );

        return back()->with(
            'success',
            'Excel row '
            . $row->row_number
            . ' was removed from the import.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Clean Rows
    |--------------------------------------------------------------------------
    */

    public function approveValid(
        Request $request,
        MembershipImportBatch $batch
    ): RedirectResponse {
        $rows = MembershipImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->where('validation_status', 'valid')
            ->where('duplicate_status', 'none')
            ->where('review_decision', 'pending')
            ->get();

        foreach ($rows as $row) {
            $row->update([
                'review_decision' => 'create',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => 'Automatically approved as a valid non-duplicate row.',
            ]);
        }

        $this->refreshBatchCounters($batch);

        $this->auditService->log(
            eventType: 'membership_import',
            module: 'Pensions Administration - Updates',
            action: 'APPROVE_VALID_IMPORT_ROWS',
            description: number_format($rows->count())
                . ' valid membership rows were approved.',
            auditable: $batch,
            metadata: [
                'approved_rows' => $rows->count(),
            ],
            request: $request
        );

        return back()->with(
            'success',
            number_format($rows->count())
            . ' valid member rows were approved for import.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Reject All Current Error Rows
    |--------------------------------------------------------------------------
    */

    public function rejectErrors(
        Request $request,
        MembershipImportBatch $batch
    ): RedirectResponse {
        $rows = MembershipImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->where('validation_status', 'error')
            ->where('review_decision', 'pending')
            ->get();

        foreach ($rows as $row) {
            $row->update([
                'review_decision' => 'reject',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'review_notes' => 'Rejected because unresolved validation errors remained.',
            ]);
        }

        $this->refreshBatchCounters($batch);

        return back()->with(
            'success',
            number_format($rows->count())
            . ' error rows were removed from the import.'
        );
    }

    private function refreshBatchCounters(
        MembershipImportBatch $batch
    ): void {
        $batch->update([
            'valid_rows' => $batch->rows()
                ->where('validation_status', 'valid')
                ->count(),

            'warning_rows' => $batch->rows()
                ->where('validation_status', 'warning')
                ->count(),

            'error_rows' => $batch->rows()
                ->where('validation_status', 'error')
                ->count(),

            'duplicate_rows' => $batch->rows()
                ->where('duplicate_status', '<>', 'none')
                ->count(),

            'approved_rows' => $batch->rows()
                ->whereIn('review_decision', [
                    'create',
                    'update',
                    'use_existing',
                    'ignore_warning',
                ])
                ->count(),

            'rejected_rows' => $batch->rows()
                ->where('review_decision', 'reject')
                ->count(),
        ]);
    }

    private function ensureRowBelongsToBatch(
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): void {
        abort_unless(
            (int) $row->import_batch_id === (int) $batch->id,
            404
        );
    }
}