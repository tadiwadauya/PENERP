<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\Audit\AuditService;
use App\Services\PensionsAdministration\Contributions\ContributionBatchRollbackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ContributionBatchRollbackController extends Controller
{
    public function __construct(
        private readonly ContributionBatchRollbackService $rollbackService,
        private readonly AuditService $auditService
    ) {
    }

    public function destroy(Request $request, ContributionImportBatch $batch): RedirectResponse
    {
        $this->ensurePermission('contributions.monthly-imports.post');

        $validated = $request->validate([
            'rollback_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $batch->load(['employer', 'contributionPeriod', 'uploadedBy', 'approvedBy', 'postedBy']);

        $oldValues = $this->auditService->values($batch);
        $batchId = $batch->id;
        $employerName = $batch->employer?->name ?? 'Unknown Employer';
        $periodLabel = $batch->contributionPeriod?->period_label
            ?? ($batch->contributionPeriod
                ? sprintf('%04d-%02d', (int) $batch->contributionPeriod->period_year, (int) $batch->contributionPeriod->period_month)
                : 'Unknown Period');

        try {
            $result = $this->rollbackService->rollbackAndDelete(
                $batch,
                (int) auth()->id(),
                $validated['rollback_reason']
            );

            $this->auditService->log(
                eventType: 'contribution_rollback',
                module: 'Pensions Administration - Contributions',
                action: 'ROLLBACK_DELETE_MONTHLY_CONTRIBUTION_BATCH',
                description: 'Monthly contribution batch #' . $batchId . ' for ' . $employerName . ' - ' . $periodLabel . ' was rolled back and deleted.',
                auditable: $batch,
                oldValues: $oldValues,
                newValues: null,
                metadata: $result + [
                    'rolled_back_by' => auth()->id(),
                ],
                request: $request
            );

            $message = 'Contribution batch #' . $batchId . ' (' . $periodLabel . ') was rolled back and deleted successfully. '
                . number_format($result['deleted_contributions']) . ' posted contribution row(s) were removed.';

            if ($result['retained_created_members'] > 0) {
                $message .= ' ' . number_format($result['retained_created_members'])
                    . ' member master record(s) created by the original upload were retained for safety and can be matched during re-upload.';
            }

            return redirect()
                ->route('pensions-administration.contributions.imports.index')
                ->with('success', $message);
        } catch (Throwable $e) {
            report($e);

            $this->auditService->failure(
                eventType: 'contribution_rollback',
                module: 'Pensions Administration - Contributions',
                action: 'ROLLBACK_DELETE_MONTHLY_CONTRIBUTION_BATCH',
                description: 'Rollback of monthly contribution batch #' . $batchId . ' failed.',
                failureReason: $e->getMessage(),
                auditable: $batch,
                metadata: [
                    'batch_id' => $batchId,
                    'rollback_reason' => $validated['rollback_reason'],
                    'requested_by' => auth()->id(),
                ],
                request: $request
            );

            return back()->with('error', $e->getMessage());
        }
    }

    private function ensurePermission(string $permission): void
    {
        $user = auth()->user();

        abort_if(!$user, 401, 'Unauthenticated.');

        if ($user->is_system_administrator) {
            return;
        }

        abort_unless(
            $user->can($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
