<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Contributions\ContributionPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContributionImportReviewController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Review Contribution Import
    |--------------------------------------------------------------------------
    */

 public function index(
    Request $request,
    ContributionImportBatch $batch
): View {
    $this->ensurePermission(
        'contributions.monthly-imports.view'
    );


    /*
    |--------------------------------------------------------------------------
    | Load Batch Relationships
    |--------------------------------------------------------------------------
    */

    $batch->load([
        'employer',
        'contributionPeriod',
        'uploadedBy',
        'approvedBy',
        'postedBy',
    ]);


    /*
    |--------------------------------------------------------------------------
    | Ensure Contribution Period Exists
    |--------------------------------------------------------------------------
    */

    abort_unless(
        $batch->contributionPeriod,
        404,
        'The contribution period for this batch could not be found.'
    );


    /*
    |--------------------------------------------------------------------------
    | Contribution Rows
    |--------------------------------------------------------------------------
    */

    $query =
        ContributionImportRow::query()
            ->with([
                'matchedMember',
                'createdMember',
            ])
            ->where(
                'import_batch_id',
                $batch->id
            )
            ->orderBy(
                'row_number'
            );


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
            trim(
                $request->input(
                    'search'
                )
            );


        $query->where(
            'normalized_data',
            'like',
            '%'
            . $search
            . '%'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Validation Status Filter
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
    | Member Type Filter
    |--------------------------------------------------------------------------
    */

    if (
        $request->filled(
            'member_type'
        )
    ) {

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
    }


    /*
    |--------------------------------------------------------------------------
    | Paginate Contribution Rows
    |--------------------------------------------------------------------------
    */

    $rows =
        $query
            ->paginate(
                50,
                [
                    '*',
                ],
                'rows_page'
            )
            ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | Current Period Nil Contributors
    |--------------------------------------------------------------------------
    */

    $nilContributors =
        ContributionPeriodMemberStatus::query()
            ->with([
                'member.currentEmployment.employer',
            ])
            ->where(
                'import_batch_id',
                $batch->id
            )
            ->where(
                'contribution_status',
                'nil_contributor'
            )
            ->orderBy(
                'member_id'
            )
            ->paginate(
                25,
                [
                    '*',
                ],
                'nil_page'
            )
            ->withQueryString();


    /*
    |--------------------------------------------------------------------------
    | Previous Contribution Period
    |--------------------------------------------------------------------------
    |
    | Reinstatements are determined by comparing the current contribution
    | schedule to the immediately preceding contribution period for the
    | same employer.
    |
    */

    $previousPeriod =
        ContributionPeriod::query()
            ->where(
                'employer_id',
                $batch->employer_id
            )
            ->where(
                'period_date',
                '<',
                $batch
                    ->contributionPeriod
                    ->period_date
            )
            ->orderByDesc(
                'period_date'
            )
            ->first();


    /*
    |--------------------------------------------------------------------------
    | Reinstatements
    |--------------------------------------------------------------------------
    |
    | Business Rule:
    |
    | A member is considered reinstated for the current period when:
    |
    | 1. The member was classified as a NIL CONTRIBUTOR in the immediately
    |    preceding contribution period; and
    |
    | 2. The member appears again on the current contribution schedule; and
    |
    | 3. The member is an existing member, not a proposed new member; and
    |
    | 4. The current contribution row is valid or carries only warnings.
    |
    */

    $reinstatedMemberIds =
        collect();


    if ($previousPeriod) {

        /*
        |--------------------------------------------------------------------------
        | Members Who Were Nil In Previous Period
        |--------------------------------------------------------------------------
        */

        $previousNilMemberIds =
            ContributionPeriodMemberStatus::query()
                ->where(
                    'contribution_period_id',
                    $previousPeriod->id
                )
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->pluck(
                    'member_id'
                )
                ->filter()
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Existing Members Contributing In Current Period
        |--------------------------------------------------------------------------
        */

        $currentContributingMemberIds =
            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'is_new_member',
                    false
                )
                ->whereNotNull(
                    'matched_member_id'
                )
                ->whereIn(
                    'validation_status',
                    [
                        'valid',
                        'warning',
                    ]
                )
                ->pluck(
                    'matched_member_id'
                )
                ->filter()
                ->unique()
                ->values();


        /*
        |--------------------------------------------------------------------------
        | Intersection = Reinstatements
        |--------------------------------------------------------------------------
        */

        $reinstatedMemberIds =
            $currentContributingMemberIds
                ->intersect(
                    $previousNilMemberIds
                )
                ->unique()
                ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | Reinstatement Count
    |--------------------------------------------------------------------------
    */

    $reinstatementCount =
        $reinstatedMemberIds
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    $currency =
        strtoupper(
            $batch->currency_code
            ??
            'ZWG'
        );


    if (
        !in_array(
            $currency,
            [
                'ZWG',
                'USD',
            ],
            true
        )
    ) {
        $currency =
            'ZWG';
    }


    /*
    |--------------------------------------------------------------------------
    | ZWG Totals
    |--------------------------------------------------------------------------
    |
    | We always expose both ZWG and USD values to the view even when the
    | current batch has one primary currency. This keeps the module fully
    | multi-currency and allows the review/show pages to display both.
    |
    */

    $zwgBasicPayTotal =
        (float) (
            $batch
                ->zwg_basic_pay_total
            ??
            0
        );


    $zwgEmployeeContributionTotal =
        (float) (
            $batch
                ->zwg_employee_contribution_total
            ??
            0
        );


    $zwgEmployerContributionTotal =
        (float) (
            $batch
                ->zwg_employer_contribution_total
            ??
            0
        );


    $zwgEmployeeAvcTotal =
        (float) (
            $batch
                ->zwg_employee_avc_total
            ??
            0
        );


    $zwgEmployerAvcTotal =
        (float) (
            $batch
                ->zwg_employer_avc_total
            ??
            0
        );


    /*
    |--------------------------------------------------------------------------
    | USD Totals
    |--------------------------------------------------------------------------
    */

    $usdBasicPayTotal =
        (float) (
            $batch
                ->usd_basic_pay_total
            ??
            0
        );


    $usdEmployeeContributionTotal =
        (float) (
            $batch
                ->usd_employee_contribution_total
            ??
            0
        );


    $usdEmployerContributionTotal =
        (float) (
            $batch
                ->usd_employer_contribution_total
            ??
            0
        );


    $usdEmployeeAvcTotal =
        (float) (
            $batch
                ->usd_employee_avc_total
            ??
            0
        );


    $usdEmployerAvcTotal =
        (float) (
            $batch
                ->usd_employer_avc_total
            ??
            0
        );


    /*
    |--------------------------------------------------------------------------
    | Primary Currency Financial Totals
    |--------------------------------------------------------------------------
    |
    | These values remain for compatibility with the current review Blade.
    |
    */

    if (
        $currency
        ===
        'USD'
    ) {

        $basicPayTotal =
            $usdBasicPayTotal;


        $employeeContributionTotal =
            $usdEmployeeContributionTotal;


        $employerContributionTotal =
            $usdEmployerContributionTotal;


        $employeeAvcTotal =
            $usdEmployeeAvcTotal;


        $employerAvcTotal =
            $usdEmployerAvcTotal;

    } else {

        $basicPayTotal =
            $zwgBasicPayTotal;


        $employeeContributionTotal =
            $zwgEmployeeContributionTotal;


        $employerContributionTotal =
            $zwgEmployerContributionTotal;


        $employeeAvcTotal =
            $zwgEmployeeAvcTotal;


        $employerAvcTotal =
            $zwgEmployerAvcTotal;
    }


    /*
    |--------------------------------------------------------------------------
    | Rows Eligible For Posting
    |--------------------------------------------------------------------------
    |
    | Valid rows and warning rows can be posted.
    |
    | Error rows cannot be posted.
    |
    */

    $postableRows =
        ContributionImportRow::query()
            ->where(
                'import_batch_id',
                $batch->id
            )
            ->whereIn(
                'validation_status',
                [
                    'valid',
                    'warning',
                ]
            )
            ->count();


    /*
    |--------------------------------------------------------------------------
    | Review Summary
    |--------------------------------------------------------------------------
    */

    $summary = [

        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        'currency' =>
            $currency,


        /*
        |--------------------------------------------------------------------------
        | Contribution Period
        |--------------------------------------------------------------------------
        */

        'current_period' =>
            $batch
                ->contributionPeriod
                ->period_label,

        'previous_period' =>
            $previousPeriod
                ?->period_label,

        'previous_period_id' =>
            $previousPeriod
                ?->id,


        /*
        |--------------------------------------------------------------------------
        | Member Counts
        |--------------------------------------------------------------------------
        */

        'total_rows' =>
            (int) (
                $batch
                    ->total_rows
                ??
                0
            ),

        'postable_rows' =>
            $postableRows,

        'existing_members' =>
            (int) (
                $batch
                    ->existing_member_rows
                ??
                0
            ),

        'new_members' =>
            (int) (
                $batch
                    ->new_member_rows
                ??
                0
            ),

        'reinstatements' =>
            $reinstatementCount,

        'nil_contributors' =>
            (int) (
                $batch
                    ->nil_contributor_rows
                ??
                0
            ),


        /*
        |--------------------------------------------------------------------------
        | Validation Counts
        |--------------------------------------------------------------------------
        */

        'valid_rows' =>
            (int) (
                $batch
                    ->valid_rows
                ??
                0
            ),

        'warning_rows' =>
            (int) (
                $batch
                    ->warning_rows
                ??
                0
            ),

        'error_rows' =>
            (int) (
                $batch
                    ->error_rows
                ??
                0
            ),


        /*
        |--------------------------------------------------------------------------
        | Primary Currency Financial Values
        |--------------------------------------------------------------------------
        |
        | These continue to support the existing Blade.
        |
        */

        'basic_pay_total' =>
            $basicPayTotal,

        'employee_contribution_total' =>
            $employeeContributionTotal,

        'employer_contribution_total' =>
            $employerContributionTotal,

        'employee_avc_total' =>
            $employeeAvcTotal,

        'employer_avc_total' =>
            $employerAvcTotal,


        /*
        |--------------------------------------------------------------------------
        | ZWG Financial Values
        |--------------------------------------------------------------------------
        */

        'zwg_basic_pay_total' =>
            $zwgBasicPayTotal,

        'zwg_employee_contribution_total' =>
            $zwgEmployeeContributionTotal,

        'zwg_employer_contribution_total' =>
            $zwgEmployerContributionTotal,

        'zwg_employee_avc_total' =>
            $zwgEmployeeAvcTotal,

        'zwg_employer_avc_total' =>
            $zwgEmployerAvcTotal,


        /*
        |--------------------------------------------------------------------------
        | USD Financial Values
        |--------------------------------------------------------------------------
        */

        'usd_basic_pay_total' =>
            $usdBasicPayTotal,

        'usd_employee_contribution_total' =>
            $usdEmployeeContributionTotal,

        'usd_employer_contribution_total' =>
            $usdEmployerContributionTotal,

        'usd_employee_avc_total' =>
            $usdEmployeeAvcTotal,

        'usd_employer_avc_total' =>
            $usdEmployerAvcTotal,
    ];


    /*
    |--------------------------------------------------------------------------
    | Return Review
    |--------------------------------------------------------------------------
    */

    return view(
        'pensions-administration.contributions.imports.review',
        compact(
            'batch',
            'rows',
            'nilContributors',
            'summary',
            'reinstatedMemberIds'
        )
    );
}


    /*
    |--------------------------------------------------------------------------
    | Permission Enforcement
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