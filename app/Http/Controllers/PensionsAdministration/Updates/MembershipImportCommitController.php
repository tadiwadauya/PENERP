<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Jobs\PensionsAdministration\Updates\ImportApprovedMembers;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MembershipImportCommitController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Start Final Import
    |--------------------------------------------------------------------------
    */

    public function store(
        MembershipImportBatch $batch
    ): RedirectResponse {
        /*
        |--------------------------------------------------------------------------
        | Batch Must Be Ready for Review / Import
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $batch->status,
                [
                    'awaiting_review',
                    'failed',
                ],
                true
            )
        ) {
            return back()->with(
                'error',
                'This membership batch cannot be imported from its current status.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Count Approved Rows
        |--------------------------------------------------------------------------
        */

        $approvedRows =
            $batch->rows()
                ->whereIn(
                    'review_decision',
                    [
                        'create',
                        'update',
                        'use_existing',
                        'ignore_warning',
                    ]
                )
                ->whereNull(
                    'imported_at'
                )
                ->where(
                    'validation_status',
                    '<>',
                    'error'
                )
                ->count();


        if ($approvedRows === 0) {
            return back()->with(
                'error',
                'There are no approved membership rows available for import.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Mark Queued
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' =>
                'processing',

            'approved_rows' =>
                $approvedRows,

            'progress_percentage' =>
                0,

            'failure_reason' =>
                null,
        ]);


        /*
        |--------------------------------------------------------------------------
        | Queue Final Import
        |--------------------------------------------------------------------------
        */

        ImportApprovedMembers::dispatch(
            $batch->id
        );


        return redirect()
            ->route(
                'pensions-administration.updates.imports.show',
                $batch
            )
            ->with(
                'success',
                number_format($approvedRows)
                . ' approved member rows have been queued for final import.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Import Progress
    |--------------------------------------------------------------------------
    */

    public function status(
        MembershipImportBatch $batch
    ): JsonResponse {
        $batch->refresh();


        return response()->json([
            'id' =>
                $batch->id,

            'status' =>
                $batch->status,

            'status_label' =>
                $batch->status_label,

            'approved_rows' =>
                (int)
                $batch->approved_rows,

            'imported_rows' =>
                (int)
                $batch->imported_rows,

            'progress_percentage' =>
                (float)
                $batch->progress_percentage,

            'failure_reason' =>
                $batch->failure_reason,

            'completed_at' =>
                $batch->completed_at
                    ? $batch->completed_at->format(
                        'Y-m-d H:i:s'
                    )
                    : null,
        ]);
    }
}