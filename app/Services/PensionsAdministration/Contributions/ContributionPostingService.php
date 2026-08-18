<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ContributionPostingService
{
    /*
    |--------------------------------------------------------------------------
    | Processing Chunk Size
    |--------------------------------------------------------------------------
    |
    | Rows are read from staging in manageable chunks so that large monthly
    | schedules do not need to be loaded completely into memory.
    |
    */

    private const PROCESS_CHUNK_SIZE = 500;


    /*
    |--------------------------------------------------------------------------
    | SQL Server Insert Chunk Size
    |--------------------------------------------------------------------------
    |
    | SQL Server has a parameter limit per statement.
    |
    | member_contributions contains many columns, therefore bulk inserts are
    | deliberately kept small.
    |
    */

    private const INSERT_CHUNK_SIZE = 25;


    public function __construct(
        private readonly ContributionNewMemberService $newMemberService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Post Monthly Expected Contributions
    |--------------------------------------------------------------------------
    */

    public function post(
        ContributionImportBatch $batch,
        int $postedBy
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Reload Batch
        |--------------------------------------------------------------------------
        */

        $batch =
            ContributionImportBatch::query()
                ->with([
                    'contributionPeriod',
                ])
                ->findOrFail(
                    $batch->id
                );


        /*
        |--------------------------------------------------------------------------
        | Workflow Validation
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $batch->status,
                [
                    'approved',
                    'posting',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Only an approved contribution batch can be posted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Double Posting
        |--------------------------------------------------------------------------
        */

        if (
            $batch->posted_at
            ||
            $batch->status === 'posted'
        ) {
            throw new RuntimeException(
                'This contribution batch has already been posted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validation Errors
        |--------------------------------------------------------------------------
        |
        | Warnings DO NOT block posting.
        |
        */

        if (
            (int) $batch->error_rows > 0
        ) {
            throw new RuntimeException(
                'The contribution batch contains validation errors and cannot be posted.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Contribution Period
        |--------------------------------------------------------------------------
        */

        $period =
            $batch->contributionPeriod;


        if (!$period) {
            throw new RuntimeException(
                'The contribution batch does not have a valid contribution period.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Period Year
        |--------------------------------------------------------------------------
        */

        $periodYear =
            (int) (
                $period->period_year
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | Period Month
        |--------------------------------------------------------------------------
        */

        $periodMonth =
            (int) (
                $period->period_month
                ?? 0
            );


        if (
            $periodYear <= 0
            ||
            $periodMonth < 1
            ||
            $periodMonth > 12
        ) {
            throw new RuntimeException(
                'The contribution period does not contain a valid year and month.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Period Date
        |--------------------------------------------------------------------------
        */

        $periodDate =
            $this->resolvePeriodDate(
                periodYear:
                    $periodYear,

                periodMonth:
                    $periodMonth,

                existingPeriodDate:
                    $period->period_date
                    ?? null
            );


        /*
        |--------------------------------------------------------------------------
        | Due Date
        |--------------------------------------------------------------------------
        */

        $dueDate =
            $this->resolveDueDate(
                batch:
                    $batch,

                period:
                    $period,

                periodDate:
                    $periodDate
            );


        /*
        |--------------------------------------------------------------------------
        | Batch Currency
        |--------------------------------------------------------------------------
        */

        $batchCurrency =
            strtoupper(
                trim(
                    (string) (
                        $batch->currency_code
                        ??
                        'ZWG'
                    )
                )
            );


        if (
            !in_array(
                $batchCurrency,
                [
                    'ZWG',
                    'USD',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'Unsupported contribution currency: '
                . $batchCurrency
                . '.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Contribution Rows
        |--------------------------------------------------------------------------
        */

        $totalRows =
            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->count();


        if (
            $totalRows === 0
        ) {
            throw new RuntimeException(
                'There are no contribution rows available for posting.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Existing Posted Records
        |--------------------------------------------------------------------------
        |
        | This is an additional safety check before starting.
        |
        */

        $existingPostedRows =
            DB::table(
                'member_contributions'
            )
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->count();


        if (
            $existingPostedRows > 0
        ) {
            throw new RuntimeException(
                'Contribution records already exist for this batch. '
                . 'Posting has been stopped to prevent duplicate expected contributions.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | member_contributions Schema
        |--------------------------------------------------------------------------
        |
        | Read SQL Server metadata ONCE.
        |
        */

        $contributionColumns =
            Schema::getColumnListing(
                'member_contributions'
            );


        $availableColumns =
            array_flip(
                $contributionColumns
            );


        /*
        |--------------------------------------------------------------------------
        | Required member_contributions Columns
        |--------------------------------------------------------------------------
        */

        $requiredColumns = [
            'member_id',
            'employer_id',
            'contribution_period_id',
            'source_system',
            'period_date',
            'period_year',
            'period_month',
            'transaction_type',
        ];


        foreach (
            $requiredColumns
            as $requiredColumn
        ) {
            if (
                !isset(
                    $availableColumns[
                        $requiredColumn
                    ]
                )
            ) {
                throw new RuntimeException(
                    'The member_contributions table does not contain required column: '
                    . $requiredColumn
                    . '.'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Start Posting
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' =>
                'posting',

            'progress_percentage' =>
                5,

            'posted_rows' =>
                0,

            'failure_reason' =>
                null,

            'completed_at' =>
                null,
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | Atomic Posting Transaction
            |--------------------------------------------------------------------------
            |
            | Expected contribution posting is financial/member history data.
            |
            | If an error occurs, the complete posting transaction is rolled
            | back instead of leaving a partially posted contribution batch.
            |
            */

            DB::transaction(
                function () use (
                    $batch,
                    $postedBy,
                    $period,
                    $periodYear,
                    $periodMonth,
                    $periodDate,
                    $dueDate,
                    $batchCurrency,
                    $totalRows,
                    $availableColumns
                ): void {

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Batch
                    |--------------------------------------------------------------------------
                    */

                    $lockedBatch =
                        ContributionImportBatch::query()
                            ->with([
                                'contributionPeriod',
                            ])
                            ->where(
                                'id',
                                $batch->id
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | Recheck Workflow
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !in_array(
                            $lockedBatch->status,
                            [
                                'approved',
                                'posting',
                            ],
                            true
                        )
                    ) {
                        throw new RuntimeException(
                            'This contribution batch is no longer available for posting.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Recheck Double Posting
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $lockedBatch->posted_at
                        ||
                        $lockedBatch->status === 'posted'
                    ) {
                        throw new RuntimeException(
                            'This contribution batch has already been posted.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Financial Records Double Check
                    |--------------------------------------------------------------------------
                    */

                    $alreadyInserted =
                        DB::table(
                            'member_contributions'
                        )
                            ->where(
                                'import_batch_id',
                                $lockedBatch->id
                            )
                            ->exists();


                    if ($alreadyInserted) {
                        throw new RuntimeException(
                            'Contribution records already exist for this batch.'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Posting Counters
                    |--------------------------------------------------------------------------
                    */

                    $postedRows =
                        0;


                    /*
                    |--------------------------------------------------------------------------
                    | Process Contribution Rows
                    |--------------------------------------------------------------------------
                    */

                    ContributionImportRow::query()
                        ->where(
                            'import_batch_id',
                            $lockedBatch->id
                        )
                        ->orderBy(
                            'id'
                        )
                        ->chunkById(
                            self::PROCESS_CHUNK_SIZE,

                            function (
                                $rows
                            ) use (
                                $lockedBatch,
                                $postedBy,
                                $period,
                                $periodYear,
                                $periodMonth,
                                $periodDate,
                                $dueDate,
                                $batchCurrency,
                                $totalRows,
                                $availableColumns,
                                &$postedRows
                            ): void {

                                /*
                                |--------------------------------------------------------------------------
                                | Contribution Insert Payload
                                |--------------------------------------------------------------------------
                                */

                                $insertRows =
                                    [];


                                foreach (
                                    $rows
                                    as $row
                                ) {

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Validation Status
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        $row->validation_status
                                        ===
                                        'error'
                                    ) {
                                        throw new RuntimeException(
                                            'Contribution Excel row '
                                            . $row->row_number
                                            . ' contains a validation error.'
                                        );
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Resolve Member
                                    |--------------------------------------------------------------------------
                                    */

                                    $memberId =
                                        $row->matched_member_id;


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Proposed New Member
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        !$memberId
                                        &&
                                        $row->is_new_member
                                    ) {
                                        $newMember =
                                            $this
                                                ->newMemberService
                                                ->create(
                                                    $row
                                                );


                                        $memberId =
                                            $newMember->id;


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Refresh Row
                                        |--------------------------------------------------------------------------
                                        |
                                        | ContributionNewMemberService writes the newly generated
                                        | PENERP/PenAd number back into normalized_data.
                                        |
                                        */

                                        $row->refresh();
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Member Must Exist
                                    |--------------------------------------------------------------------------
                                    */

                                    if (!$memberId) {
                                        throw new RuntimeException(
                                            'Contribution Excel row '
                                            . $row->row_number
                                            . ' is not linked to a valid member.'
                                        );
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Normalized Excel Data
                                    |--------------------------------------------------------------------------
                                    */

                                    $data =
                                        $row->normalized_data
                                        ?? [];


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Member References
                                    |--------------------------------------------------------------------------
                                    */

                                    $penerpMemberNumber =
                                        $this->stringValue(
                                            $data[
                                                'penerp_member_number'
                                            ]
                                            ?? null
                                        );


                                    $penadMemberNumber =
                                        $this->stringValue(
                                            $data[
                                                'penad_member_number'
                                            ]
                                            ??
                                            $data[
                                                'pension_reference_number'
                                            ]
                                            ??
                                            null
                                        );


                                    $fundworxMemberNumber =
                                        $this->stringValue(
                                            $data[
                                                'fundworx_member_number'
                                            ]
                                            ?? null
                                        );


                                    $staffNumber =
                                        $this->stringValue(
                                            $data[
                                                'staff_number'
                                            ]
                                            ?? null
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Scheme Code
                                    |--------------------------------------------------------------------------
                                    */

                                    $schemeCode =
                                        $this->stringValue(
                                            $data[
                                                'scheme_code'
                                            ]
                                            ??
                                            $lockedBatch->scheme_code
                                            ??
                                            $period->scheme_code
                                            ??
                                            null
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Payment Flag
                                    |--------------------------------------------------------------------------
                                    */

                                    $paymentFlag =
                                        $this->stringValue(
                                            $data[
                                                'payment_flag'
                                            ]
                                            ?? null
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Comments
                                    |--------------------------------------------------------------------------
                                    */

                                    $comments =
                                        $this->stringValue(
                                            $data[
                                                'comments'
                                            ]
                                            ?? null
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | USD Amounts
                                    |--------------------------------------------------------------------------
                                    |
                                    | Explicit legacy USD fields take precedence.
                                    |
                                    | If this batch is USD and the current template contains
                                    | generic columns such as employee_contribution, those
                                    | generic values are mapped into USD.
                                    |
                                    */

                                    $usdBasicPay =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_basic_pay',

                                            genericKey:
                                                'basic_pay',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeRate =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_rate',

                                            genericKey:
                                                'employee_rate',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerRate =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_rate',

                                            genericKey:
                                                'employer_rate',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeContribution =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_contribution',

                                            genericKey:
                                                'employee_contribution',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerContribution =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_contribution',

                                            genericKey:
                                                'employer_contribution',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeAvc =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_avc',

                                            genericKey:
                                                'employee_avc',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerAvc =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_avc',

                                            genericKey:
                                                'employer_avc',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeArrear =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_arrear',

                                            genericKey:
                                                'employee_arrear',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerArrear =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_arrear',

                                            genericKey:
                                                'employer_arrear',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeTransferIn =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_transfer_in',

                                            genericKey:
                                                'employee_transfer_in',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerTransferIn =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_transfer_in',

                                            genericKey:
                                                'employer_transfer_in',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployeeLateInterest =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employee_late_interest',

                                            genericKey:
                                                'employee_late_interest',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    $usdEmployerLateInterest =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'usd_employer_late_interest',

                                            genericKey:
                                                'employer_late_interest',

                                            useGeneric:
                                                $batchCurrency === 'USD'
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | ZWG Amounts
                                    |--------------------------------------------------------------------------
                                    */

                                    $zwgBasicPay =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_basic_pay',

                                            genericKey:
                                                'basic_pay',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeRate =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_rate',

                                            genericKey:
                                                'employee_rate',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerRate =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_rate',

                                            genericKey:
                                                'employer_rate',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeContribution =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_contribution',

                                            genericKey:
                                                'employee_contribution',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerContribution =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_contribution',

                                            genericKey:
                                                'employer_contribution',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeAvc =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_avc',

                                            genericKey:
                                                'employee_avc',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerAvc =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_avc',

                                            genericKey:
                                                'employer_avc',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeArrear =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_arrear',

                                            genericKey:
                                                'employee_arrear',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerArrear =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_arrear',

                                            genericKey:
                                                'employer_arrear',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeTransferIn =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_transfer_in',

                                            genericKey:
                                                'employee_transfer_in',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerTransferIn =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_transfer_in',

                                            genericKey:
                                                'employer_transfer_in',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployeeLateInterest =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employee_late_interest',

                                            genericKey:
                                                'employee_late_interest',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    $zwgEmployerLateInterest =
                                        $this->financialValue(
                                            data:
                                                $data,

                                            specificKey:
                                                'zwg_employer_late_interest',

                                            genericKey:
                                                'employer_late_interest',

                                            useGeneric:
                                                $batchCurrency === 'ZWG'
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Candidate Database Payload
                                    |--------------------------------------------------------------------------
                                    */

                                    $candidatePayload = [

                                        /*
                                        |--------------------------------------------------------------------------
                                        | Relationships
                                        |--------------------------------------------------------------------------
                                        */

                                        'member_id' =>
                                            $memberId,

                                        'employer_id' =>
                                            $lockedBatch->employer_id,

                                        'contribution_period_id' =>
                                            $lockedBatch
                                                ->contribution_period_id,

                                        'import_batch_id' =>
                                            $lockedBatch->id,

                                        'import_row_id' =>
                                            $row->id,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Source Information
                                        |--------------------------------------------------------------------------
                                        */

                                        'source_row_number' =>
                                            $row->row_number,

                                        'source_system' =>
                                            'PENERP',


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Member References
                                        |--------------------------------------------------------------------------
                                        */

                                        'penerp_member_number' =>
                                            $penerpMemberNumber,

                                        'penad_member_number' =>
                                            $penadMemberNumber,

                                        'fundworx_member_number' =>
                                            $fundworxMemberNumber,

                                        'staff_number' =>
                                            $staffNumber,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Contribution Period
                                        |--------------------------------------------------------------------------
                                        */

                                        'period_date' =>
                                            $periodDate,

                                        'period_year' =>
                                            $periodYear,

                                        'period_month' =>
                                            $periodMonth,

                                        'due_date' =>
                                            $dueDate,

                                        'scheme_code' =>
                                            $schemeCode,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Transaction Classification
                                        |--------------------------------------------------------------------------
                                        |
                                        | These are EXPECTED contributions / contributions due.
                                        |
                                        | They are NOT employer cash receipts.
                                        |
                                        */

                                        'transaction_type' =>
                                            'expected',

                                        'payment_flag' =>
                                            $paymentFlag,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | USD
                                        |--------------------------------------------------------------------------
                                        */

                                        'usd_basic_pay' =>
                                            $usdBasicPay,

                                        'usd_employee_rate' =>
                                            $usdEmployeeRate,

                                        'usd_employer_rate' =>
                                            $usdEmployerRate,

                                        'usd_employee_contribution' =>
                                            $usdEmployeeContribution,

                                        'usd_employer_contribution' =>
                                            $usdEmployerContribution,

                                        'usd_employee_avc' =>
                                            $usdEmployeeAvc,

                                        'usd_employer_avc' =>
                                            $usdEmployerAvc,

                                        'usd_employee_arrear' =>
                                            $usdEmployeeArrear,

                                        'usd_employer_arrear' =>
                                            $usdEmployerArrear,

                                        'usd_employee_transfer_in' =>
                                            $usdEmployeeTransferIn,

                                        'usd_employer_transfer_in' =>
                                            $usdEmployerTransferIn,

                                        'usd_employee_late_interest' =>
                                            $usdEmployeeLateInterest,

                                        'usd_employer_late_interest' =>
                                            $usdEmployerLateInterest,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | ZWG
                                        |--------------------------------------------------------------------------
                                        */

                                        'zwg_basic_pay' =>
                                            $zwgBasicPay,

                                        'zwg_employee_rate' =>
                                            $zwgEmployeeRate,

                                        'zwg_employer_rate' =>
                                            $zwgEmployerRate,

                                        'zwg_employee_contribution' =>
                                            $zwgEmployeeContribution,

                                        'zwg_employer_contribution' =>
                                            $zwgEmployerContribution,

                                        'zwg_employee_avc' =>
                                            $zwgEmployeeAvc,

                                        'zwg_employer_avc' =>
                                            $zwgEmployerAvc,

                                        'zwg_employee_arrear' =>
                                            $zwgEmployeeArrear,

                                        'zwg_employer_arrear' =>
                                            $zwgEmployerArrear,

                                        'zwg_employee_transfer_in' =>
                                            $zwgEmployeeTransferIn,

                                        'zwg_employer_transfer_in' =>
                                            $zwgEmployerTransferIn,

                                        'zwg_employee_late_interest' =>
                                            $zwgEmployeeLateInterest,

                                        'zwg_employer_late_interest' =>
                                            $zwgEmployerLateInterest,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Comments
                                        |--------------------------------------------------------------------------
                                        */

                                        'comments' =>
                                            $comments,


                                        /*
                                        |--------------------------------------------------------------------------
                                        | Posting Audit
                                        |--------------------------------------------------------------------------
                                        */

                                        'posted_by' =>
                                            $postedBy,

                                        'posted_at' =>
                                            now(),

                                        'created_by' =>
                                            $postedBy,

                                        'updated_by' =>
                                            $postedBy,

                                        'created_at' =>
                                            now(),

                                        'updated_at' =>
                                            now(),
                                    ];


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Keep Only Real Database Columns
                                    |--------------------------------------------------------------------------
                                    */

                                    $payload =
                                        array_intersect_key(
                                            $candidatePayload,
                                            $availableColumns
                                        );


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Mandatory Values Safety Check
                                    |--------------------------------------------------------------------------
                                    */

                                    foreach (
                                        [
                                            'member_id',
                                            'employer_id',
                                            'contribution_period_id',
                                            'source_system',
                                            'period_date',
                                            'period_year',
                                            'period_month',
                                            'transaction_type',
                                        ]
                                        as $requiredField
                                    ) {
                                        if (
                                            !array_key_exists(
                                                $requiredField,
                                                $payload
                                            )
                                            ||
                                            $payload[
                                                $requiredField
                                            ]
                                            ===
                                            null
                                        ) {
                                            throw new RuntimeException(
                                                'Required contribution field '
                                                . $requiredField
                                                . ' could not be resolved for Excel row '
                                                . $row->row_number
                                                . '.'
                                            );
                                        }
                                    }


                                    /*
                                    |--------------------------------------------------------------------------
                                    | Add To Bulk Insert
                                    |--------------------------------------------------------------------------
                                    */

                                    $insertRows[] =
                                        $payload;
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | SQL Server Safe Bulk Insert
                                |--------------------------------------------------------------------------
                                */

                                foreach (
                                    array_chunk(
                                        $insertRows,
                                        self::INSERT_CHUNK_SIZE
                                    )
                                    as $insertChunk
                                ) {
                                    DB::table(
                                        'member_contributions'
                                    )->insert(
                                        $insertChunk
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Contribution Period Member Status
                                |--------------------------------------------------------------------------
                                */

                                foreach (
                                    $rows
                                    as $row
                                ) {
                                    $memberId =
                                        $row->matched_member_id
                                        ??
                                        $row->created_member_id;


                                    if (!$memberId) {
                                        continue;
                                    }


                                    ContributionPeriodMemberStatus::updateOrCreate(
                                        [
                                            'contribution_period_id' =>
                                                $lockedBatch
                                                    ->contribution_period_id,

                                            'member_id' =>
                                                $memberId,
                                        ],
                                        [
                                            'employer_id' =>
                                                $lockedBatch
                                                    ->employer_id,

                                            'contribution_status' =>
                                                'contributed',

                                            'reason' =>
                                                'Expected contribution posted from approved monthly contribution schedule.',

                                            'import_batch_id' =>
                                                $lockedBatch->id,
                                        ]
                                    );
                                }


                                /*
                                |--------------------------------------------------------------------------
                                | Posted Row Count
                                |--------------------------------------------------------------------------
                                */

                                $postedRows +=
                                    count(
                                        $insertRows
                                    );


                                /*
                                |--------------------------------------------------------------------------
                                | Posting Progress
                                |--------------------------------------------------------------------------
                                */

                                $rowProgress =
                                    $postedRows
                                    /
                                    max(
                                        1,
                                        $totalRows
                                    );


                                $progressPercentage =
                                    10
                                    +
                                    (
                                        $rowProgress
                                        *
                                        80
                                    );


                                $lockedBatch->update([
                                    'posted_rows' =>
                                        $postedRows,

                                    'progress_percentage' =>
                                        round(
                                            min(
                                                90,
                                                $progressPercentage
                                            ),
                                            2
                                        ),
                                ]);
                            }
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | Verify Complete Batch
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $postedRows !== $totalRows
                    ) {
                        throw new RuntimeException(
                            'Contribution posting stopped before all rows were posted. Expected '
                            . $totalRows
                            . ' row(s), but posted '
                            . $postedRows
                            . ' row(s).'
                        );
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Finalising
                    |--------------------------------------------------------------------------
                    */

                    $lockedBatch->update([
                        'posted_rows' =>
                            $postedRows,

                        'progress_percentage' =>
                            95,
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Contribution Period
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $lockedBatch
                            ->contributionPeriod
                    ) {
                        $lockedBatch
                            ->contributionPeriod
                            ->update([
                                'status' =>
                                    'posted',

                                'updated_by' =>
                                    $postedBy,
                            ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | Batch Posted
                    |--------------------------------------------------------------------------
                    */

                    $lockedBatch->update([
                        'status' =>
                            'posted',

                        'posted_by' =>
                            $postedBy,

                        'posted_at' =>
                            now(),

                        'posted_rows' =>
                            $postedRows,

                        'progress_percentage' =>
                            100,

                        'failure_reason' =>
                            null,

                        'completed_at' =>
                            now(),
                    ]);
                },
                3
            );

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Posting Failed
            |--------------------------------------------------------------------------
            |
            | The contribution transaction is rolled back.
            |
            */

            $batch->refresh();


            $batch->update([
                'status' =>
                    'posting_failed',

                'failure_reason' =>
                    $e->getMessage(),

                'completed_at' =>
                    now(),
            ]);


            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Period Date
    |--------------------------------------------------------------------------
    */

    private function resolvePeriodDate(
        int $periodYear,
        int $periodMonth,
        mixed $existingPeriodDate = null
    ): string {
        if (
            filled(
                $existingPeriodDate
            )
        ) {
            return Carbon::parse(
                $existingPeriodDate
            )->toDateString();
        }


        return Carbon::create(
            $periodYear,
            $periodMonth,
            1
        )
            ->endOfMonth()
            ->toDateString();
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Due Date
    |--------------------------------------------------------------------------
    */

    private function resolveDueDate(
        ContributionImportBatch $batch,
        object $period,
        string $periodDate
    ): string {
        $dueDate =
            $batch->due_date
            ??
            $period->due_date
            ??
            $periodDate;


        return Carbon::parse(
            $dueDate
        )->toDateString();
    }


    /*
    |--------------------------------------------------------------------------
    | Financial Value
    |--------------------------------------------------------------------------
    |
    | Supports BOTH:
    |
    | New template:
    |
    | employee_contribution
    | employer_contribution
    |
    | and older currency-specific templates:
    |
    | zwg_employee_contribution
    | usd_employee_contribution
    |
    | The explicit currency-specific value takes priority when it contains a
    | value. Otherwise the generic value is used when the batch currency
    | matches.
    |
    */

    private function financialValue(
        array $data,
        string $specificKey,
        string $genericKey,
        bool $useGeneric
    ): float {
        /*
        |--------------------------------------------------------------------------
        | Specific Currency Field
        |--------------------------------------------------------------------------
        */

        if (
            array_key_exists(
                $specificKey,
                $data
            )
            &&
            $data[
                $specificKey
            ]
            !==
            null
            &&
            $data[
                $specificKey
            ]
            !==
            ''
        ) {
            return (float)
                $data[
                    $specificKey
                ];
        }


        /*
        |--------------------------------------------------------------------------
        | Generic Field
        |--------------------------------------------------------------------------
        */

        if (
            $useGeneric
            &&
            array_key_exists(
                $genericKey,
                $data
            )
            &&
            $data[
                $genericKey
            ]
            !==
            null
            &&
            $data[
                $genericKey
            ]
            !==
            ''
        ) {
            return (float)
                $data[
                    $genericKey
                ];
        }


        return 0.0;
    }


    /*
    |--------------------------------------------------------------------------
    | String Value
    |--------------------------------------------------------------------------
    */

    private function stringValue(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }


        $value =
            trim(
                (string)
                $value
            );


        return $value !== ''
            ? $value
            : null;
    }
}