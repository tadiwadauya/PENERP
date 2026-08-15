<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContributionImportReviewController extends Controller
{
    public function index(
        Request $request,
        ContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.monthly-imports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
            'uploadedBy',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Contribution Rows
        |--------------------------------------------------------------------------
        */

        $query =
            ContributionImportRow::query()
                ->with([
                    'matchedMember',
                ])
                ->where(
                    'import_batch_id',
                    $batch
                        ->id
                );


        /*
        |--------------------------------------------------------------------------
        | Validation Status
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'status'
            )
        ) {
            $query->where(
                'validation_status',
                $request->input(
                    'status'
                )
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Member Type
        |--------------------------------------------------------------------------
        */

        if (
            $request->input(
                'member_type'
            )
            ===
            'new'
        ) {
            $query->where(
                'is_new_member',
                true
            );
        }


        if (
            $request->input(
                'member_type'
            )
            ===
            'existing'
        ) {
            $query->where(
                'is_new_member',
                false
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'search'
            )
        ) {

            $search =
                strtoupper(
                    trim(
                        $request->input(
                            'search'
                        )
                    )
                );


            $query->where(
                function ($query) use (
                    $search
                ): void {

                    $query
                        ->whereRaw(
                            'UPPER(CAST(normalized_data AS NVARCHAR(MAX))) LIKE ?',
                            [
                                '%'
                                . $search
                                . '%',
                            ]
                        );
                }
            );
        }


        $rows =
            $query
                ->orderBy(
                    'row_number'
                )
                ->paginate(50)
                ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | Nil Contributors
        |--------------------------------------------------------------------------
        */

        $nilContributors =
            $batch
                ->contributionPeriod
                ->memberStatuses()
                ->with([
                    'member.currentEmployment.employer',
                ])
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->orderBy(
                    'member_id'
                )
                ->paginate(
                    50,
                    [
                        '*',
                    ],
                    'nil_page'
                );


        return view(
            'pensions-administration.contributions.imports.review',
            compact(
                'batch',
                'rows',
                'nilContributors'
            )
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
            403,
            'You do not have permission to perform this action.'
        );
    }
}