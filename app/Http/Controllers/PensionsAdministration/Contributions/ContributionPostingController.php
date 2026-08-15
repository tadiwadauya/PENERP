<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Contributions\PostContributionImport;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class ContributionPostingController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    public function approve(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.approve'
        );


        $validated =
            $request->validate([
                'approval_notes' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);


        if (
            $batch->status !==
            'awaiting_review'
        ) {
            return back()
                ->with(
                    'error',
                    'Only a batch awaiting review can be approved.'
                );
        }


        if (
            $batch->error_rows
            >
            0
        ) {
            return back()
                ->with(
                    'error',
                    'The batch still contains validation errors.'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Maker / Checker
        |--------------------------------------------------------------------------
        */

        if (
            $batch->uploaded_by
            ===
            auth()->id()
        ) {
            return back()
                ->with(
                    'error',
                    'The user who uploaded the schedule cannot approve the same batch.'
                );
        }


        $oldValues =
            $this
                ->auditService
                ->values(
                    $batch
                );


        try {

            $batch->update([
                'status' =>
                    'approved',

                'approved_by' =>
                    auth()->id(),

                'approved_at' =>
                    now(),

                'approval_notes' =>
                    $validated[
                        'approval_notes'
                    ]
                    ?? null,
            ]);


            $batch
                ->contributionPeriod
                ->update([
                    'status' =>
                        'approved',

                    'updated_by' =>
                        auth()->id(),
                ]);


            $this->auditService->log(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'APPROVE_MONTHLY_CONTRIBUTIONS',

                description:
                    'Contribution batch '
                    . $batch
                        ->import_uuid
                    . ' was approved.',

                auditable:
                    $batch,

                oldValues:
                    $oldValues,

                newValues:
                    $this
                        ->auditService
                        ->values(
                            $batch
                        ),

                request:
                    $request
            );


            return back()
                ->with(
                    'success',
                    'Monthly contribution batch approved successfully.'
                );

        } catch (Throwable $e) {

            $this->auditService->failure(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'APPROVE_MONTHLY_CONTRIBUTIONS',

                description:
                    'Contribution batch approval failed.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $batch,

                request:
                    $request
            );


            throw $e;
        }
    }


    public function post(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.post'
        );


        if (
            $batch->status !==
            'approved'
        ) {
            return back()
                ->with(
                    'error',
                    'Only an approved contribution batch can be posted.'
                );
        }


        if (
            $batch
                ->contributions()
                ->exists()
        ) {
            return back()
                ->with(
                    'error',
                    'This batch has already created contribution transactions.'
                );
        }


        $batch->update([
            'status' =>
                'posting',

            'posted_by' =>
                auth()->id(),

            'progress_percentage' =>
                0,
        ]);


        $this->auditService->log(
            eventType:
                'contribution_import',

            module:
                'Pensions Administration - Contributions',

            action:
                'START_MONTHLY_CONTRIBUTION_POSTING',

            description:
                'Posting started for contribution batch '
                . $batch
                    ->import_uuid
                . '.',

            auditable:
                $batch,

            request:
                $request
        );


        PostContributionImport::dispatch(
            $batch->id
        )->onQueue(
            'contribution-imports'
        );


        return back()
            ->with(
                'success',
                'Contribution posting has started.'
            );
    }


    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();


        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );


        if (
            $user
                ->is_system_administrator
        ) {
            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403
        );
    }
}