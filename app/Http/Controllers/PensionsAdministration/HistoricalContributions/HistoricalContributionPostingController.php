<?php

namespace App\Http\Controllers\PensionsAdministration\HistoricalContributions;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\HistoricalContributions\ProcessHistoricalContributionPosting;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class HistoricalContributionPostingController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Start Historical Posting
    |--------------------------------------------------------------------------
    */

    public function store(
        HistoricalContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.approve'
        );

        try {
            if (
                !in_array(
                    $batch->status,
                    [
                        'approved',
                        'posting_failed',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'The historical contribution batch must be approved before posting.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Approved Historical Rows
            |--------------------------------------------------------------------------
            */

            $approvedRows =
                HistoricalContributionImportRow::query()
                    ->where(
                        'import_batch_id',
                        $batch->id
                    )
                    ->where(
                        'review_decision',
                        'approved'
                    )
                    ->where(
                        'duplicate_status',
                        'none'
                    )
                    ->where(
                        'validation_status',
                        '<>',
                        'error'
                    )
                    ->count();

            if (
                $approvedRows <= 0
            ) {
                throw new RuntimeException(
                    'There are no approved historical contribution records to post.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Reset Previous Failure
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'failure_reason' =>
                    null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Dispatch Posting Job
            |--------------------------------------------------------------------------
            */

            ProcessHistoricalContributionPosting::dispatch(
                $batch->id,
                (int) auth()->id()
            );

            return redirect()
                ->route(
                    'pensions-administration.historical-contributions.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    number_format(
                        $approvedRows
                    )
                    . ' approved historical contribution records have been queued for posting.'
                );

        } catch (Throwable $e) {
            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Posting Status
    |--------------------------------------------------------------------------
    */

    public function status(
        HistoricalContributionImportBatch $batch
    ): JsonResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );

        $batch->refresh();

        $approvedRows =
            HistoricalContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'review_decision',
                    'approved'
                )
                ->where(
                    'duplicate_status',
                    'none'
                )
                ->where(
                    'validation_status',
                    '<>',
                    'error'
                )
                ->count();

        return response()->json([
            'status' =>
                $batch->status,

            'status_label' =>
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $batch->status
                    )
                ),

            'progress_percentage' =>
                (float) $batch->progress_percentage,

            'approved_rows' =>
                (int) $approvedRows,

            'posted_transaction_rows' =>
                (int) $batch->posted_transaction_rows,

            'posted_service_period_rows' =>
                (int) $batch->posted_service_period_rows,

            'new_members_created' =>
                (int) $batch->new_members_created,

            'failure_reason' =>
                $batch->failure_reason,

            'posted_at' =>
                optional(
                    $batch->posted_at
                )?->format(
                    'd M Y H:i:s'
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission
    |--------------------------------------------------------------------------
    */

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
            $user->is_system_administrator
        ) {
            return;
        }

        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}