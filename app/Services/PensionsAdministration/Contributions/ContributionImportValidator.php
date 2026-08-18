<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ContributionImportValidator
{
    public function __construct(
        private readonly ContributionExcelReader $excelReader,
        private readonly ContributionMemberMatcher $memberMatcher
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Process Contribution Import
    |--------------------------------------------------------------------------
    */

    public function process(
        ContributionImportBatch $batch
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Load Required Relationships
        |--------------------------------------------------------------------------
        */

        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        /*
        |--------------------------------------------------------------------------
        | Employer Validation
        |--------------------------------------------------------------------------
        */

        if (!$batch->employer) {
            throw new RuntimeException(
                'The contribution batch does not have a valid employer.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Contribution Period Validation
        |--------------------------------------------------------------------------
        */

        if (
            !$batch->contributionPeriod
        ) {
            throw new RuntimeException(
                'The contribution batch does not have a valid contribution period.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        |
        | PENERP BASE CURRENCY = ZWG
        |
        | Supported contribution upload currencies:
        |
        | ZWG
        | USD
        |
        */

        $currency =
            strtoupper(
                trim(
                    (string) (
                        $batch->currency_code
                        ?? 'ZWG'
                    )
                )
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
            throw new RuntimeException(
                'Unsupported contribution currency: '
                . $currency
                . '. Only ZWG and USD contribution schedules are currently supported.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Stage 1 - Start Processing
        |--------------------------------------------------------------------------
        |
        | 5% means:
        |
        | - Queue job started
        | - Employer found
        | - Contribution period found
        | - Currency accepted
        |
        */

        $batch->update([
            'status' =>
                'processing',

            'progress_percentage' =>
                5,

            'processed_rows' =>
                0,

            'valid_rows' =>
                0,

            'warning_rows' =>
                0,

            'error_rows' =>
                0,

            'existing_member_rows' =>
                0,

            'new_member_rows' =>
                0,

            'nil_contributor_rows' =>
                0,

            'failure_reason' =>
                null,

            'processing_started_at' =>
                now(),

            'completed_at' =>
                null,
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | Stage 2 - Resolve Uploaded Excel File
            |--------------------------------------------------------------------------
            */

            $disk =
                Storage::disk(
                    'local'
                );


            if (
                !$disk->exists(
                    $batch->file_path
                )
            ) {
                throw new RuntimeException(
                    'The contribution Excel file could not be found at the stored location: '
                    . $batch->file_path
                );
            }


            $fullPath =
                $disk->path(
                    $batch->file_path
                );


            /*
            |--------------------------------------------------------------------------
            | File Found
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'progress_percentage' =>
                    8,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Read Excel File
            |--------------------------------------------------------------------------
            */

            $excel =
                $this
                    ->excelReader
                    ->read(
                        $fullPath
                    );


            /*
            |--------------------------------------------------------------------------
            | Stage 3 - Excel Successfully Read
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'progress_percentage' =>
                    15,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Rows
            |--------------------------------------------------------------------------
            */

            $excelRows =
                $excel[
                    'rows'
                ]
                ?? [];


            $totalRows =
                count(
                    $excelRows
                );


            if (
                $totalRows === 0
            ) {
                throw new RuntimeException(
                    'The contribution Excel file does not contain any contribution rows.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Store Total Rows Early
            |--------------------------------------------------------------------------
            |
            | This allows the progress page to immediately show:
            |
            | Processed 0 of 500
            |
            */

            $batch->update([
                'total_rows' =>
                    $totalRows,

                'progress_percentage' =>
                    17,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Clear Previous Validation Results
            |--------------------------------------------------------------------------
            |
            | This allows a previously failed batch to be safely revalidated.
            |
            */

            ContributionPeriodMemberStatus::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();


            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | Stage 4 - Staging Area Ready
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'progress_percentage' =>
                    20,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $validRows =
                0;

            $warningRows =
                0;

            $errorRows =
                0;

            $existingMemberRows =
                0;

            $newMemberRows =
                0;


            /*
            |--------------------------------------------------------------------------
            | Existing Members Appearing On Schedule
            |--------------------------------------------------------------------------
            */

            $scheduledMemberIds =
                [];


            /*
            |--------------------------------------------------------------------------
            | Duplicate Row Detection
            |--------------------------------------------------------------------------
            */

            $seenFingerprints =
                [];


            /*
            |--------------------------------------------------------------------------
            | Batch Totals
            |--------------------------------------------------------------------------
            */

            $totals = [

                /*
                |--------------------------------------------------------------------------
                | USD
                |--------------------------------------------------------------------------
                */

                'usd_basic_pay_total' =>
                    0.0,

                'usd_employee_contribution_total' =>
                    0.0,

                'usd_employer_contribution_total' =>
                    0.0,

                'usd_employee_avc_total' =>
                    0.0,

                'usd_employer_avc_total' =>
                    0.0,


                /*
                |--------------------------------------------------------------------------
                | ZWG
                |--------------------------------------------------------------------------
                */

                'zwg_basic_pay_total' =>
                    0.0,

                'zwg_employee_contribution_total' =>
                    0.0,

                'zwg_employer_contribution_total' =>
                    0.0,

                'zwg_employee_avc_total' =>
                    0.0,

                'zwg_employer_avc_total' =>
                    0.0,
            ];


            /*
            |--------------------------------------------------------------------------
            | Stage 5 - Process Excel Rows
            |--------------------------------------------------------------------------
            |
            | Row processing uses:
            |
            | 20% -> 80%
            |
            | We deliberately reserve the remaining percentage for:
            |
            | - Nil contributors
            | - Totals
            | - Period summary
            | - Final database update
            |
            */

            foreach (
                $excelRows
                as $position => $excelRow
            ) {

                /*
                |--------------------------------------------------------------------------
                | Normalized Data
                |--------------------------------------------------------------------------
                */

                $data =
                    $excelRow[
                        'normalized_data'
                    ]
                    ?? [];


                /*
                |--------------------------------------------------------------------------
                | Map Currency Values
                |--------------------------------------------------------------------------
                */

                $data =
                    $this->mapCurrencyValues(
                        $currency,
                        $data
                    );


                /*
                |--------------------------------------------------------------------------
                | Row Errors / Warnings
                |--------------------------------------------------------------------------
                */

                $errors =
                    [];

                $warnings =
                    [];


                /*
                |--------------------------------------------------------------------------
                | Required Member Information
                |--------------------------------------------------------------------------
                */

                $this->validateRequiredMemberData(
                    $data,
                    $errors
                );


                /*
                |--------------------------------------------------------------------------
                | Employer Reference
                |--------------------------------------------------------------------------
                */

                $this->validateEmployerReference(
                    $batch,
                    $data,
                    $errors
                );


                /*
                |--------------------------------------------------------------------------
                | Contribution Period
                |--------------------------------------------------------------------------
                */

                $this->validatePeriod(
                    $batch,
                    $data,
                    $warnings
                );


                /*
                |--------------------------------------------------------------------------
                | Financial Validation
                |--------------------------------------------------------------------------
                */

                $this->validateFinancialValues(
                    $currency,
                    $data,
                    $warnings
                );


                /*
                |--------------------------------------------------------------------------
                | Duplicate Row Detection
                |--------------------------------------------------------------------------
                */

                $fingerprint =
                    $this->makeFingerprint(
                        $data
                    );


                if (
                    isset(
                        $seenFingerprints[
                            $fingerprint
                        ]
                    )
                ) {
                    $errors[] =
                        'Possible duplicate contribution row. It matches Excel row '
                        . $seenFingerprints[
                            $fingerprint
                        ]
                        . '.';

                } else {

                    $seenFingerprints[
                        $fingerprint
                    ] =
                        $excelRow[
                            'row_number'
                        ]
                        ?? (
                            $position
                            +
                            2
                        );
                }


                /*
                |--------------------------------------------------------------------------
                | Member Matching
                |--------------------------------------------------------------------------
                |
                | Matching priority:
                |
                | 1. PenAd member number
                | 2. PENERP member number
                | 3. Staff number + employer
                | 4. National ID
                |
                */

                $match =
                    $this
                        ->memberMatcher
                        ->match(
                            $batch->employer,
                            $data
                        );


                $member =
                    $match[
                        'member'
                    ]
                    ?? null;


                $matchType =
                    $match[
                        'match_type'
                    ]
                    ?? null;


                $isNewMember =
                    false;


                /*
                |--------------------------------------------------------------------------
                | Identifier Conflict
                |--------------------------------------------------------------------------
                */

                if (
                    $match[
                        'conflict'
                    ]
                    ?? false
                ) {
                    $errors[] =
                        $match[
                            'message'
                        ]
                        ??
                        'The member identifiers supplied in this row conflict.';
                }


                /*
                |--------------------------------------------------------------------------
                | Existing Member
                |--------------------------------------------------------------------------
                */

                if ($member) {

                    $existingMemberRows++;


                    $scheduledMemberIds[] =
                        $member->id;


                    /*
                    |--------------------------------------------------------------------------
                    | Employer Check
                    |--------------------------------------------------------------------------
                    */

                    $this->validateExistingMemberEmployer(
                        $batch,
                        $member,
                        $warnings
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Identity Check
                    |--------------------------------------------------------------------------
                    */

                    $this->validateExistingMemberIdentity(
                        $member,
                        $data,
                        $warnings
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Monthly Contribution Status
                    |--------------------------------------------------------------------------
                    */

                    ContributionPeriodMemberStatus::updateOrCreate(
                        [
                            'contribution_period_id' =>
                                $batch
                                    ->contribution_period_id,

                            'member_id' =>
                                $member->id,
                        ],
                        [
                            'employer_id' =>
                                $batch
                                    ->employer_id,

                            'contribution_status' =>
                                'contributed',

                            'reason' =>
                                'Member appears on the monthly expected contribution schedule.',

                            'import_batch_id' =>
                                $batch->id,
                        ]
                    );

                } elseif (
                    !(
                        $match[
                            'conflict'
                        ]
                        ?? false
                    )
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | Proposed New Member
                    |--------------------------------------------------------------------------
                    */

                    $isNewMember =
                        true;


                    $matchType =
                        'new_member';


                    $this->validateNewMemberCandidate(
                        $batch,
                        $data,
                        $errors,
                        $warnings
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Count New Member Candidate
                    |--------------------------------------------------------------------------
                    */

                    if (
                        empty(
                            $errors
                        )
                    ) {
                        $newMemberRows++;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Determine Validation Status
                |--------------------------------------------------------------------------
                */

                if (
                    !empty(
                        $errors
                    )
                ) {

                    $validationStatus =
                        'error';


                    $errorRows++;

                } elseif (
                    !empty(
                        $warnings
                    )
                    ||
                    $isNewMember
                ) {

                    $validationStatus =
                        'warning';


                    $warningRows++;

                } else {

                    $validationStatus =
                        'valid';


                    $validRows++;
                }


                /*
                |--------------------------------------------------------------------------
                | Add Financial Totals
                |--------------------------------------------------------------------------
                */

                $this->addTotals(
                    $totals,
                    $data
                );


                /*
                |--------------------------------------------------------------------------
                | Store Staging Row
                |--------------------------------------------------------------------------
                */

                ContributionImportRow::create([
                    'import_batch_id' =>
                        $batch->id,

                    'row_number' =>
                        $excelRow[
                            'row_number'
                        ]
                        ?? (
                            $position
                            +
                            2
                        ),

                    'raw_data' =>
                        $excelRow[
                            'raw_data'
                        ]
                        ?? [],

                    'normalized_data' =>
                        $data,

                    'matched_member_id' =>
                        $member
                            ?->id,

                    'match_type' =>
                        $matchType,

                    'is_new_member' =>
                        $isNewMember,

                    'member_created' =>
                        false,

                    'created_member_id' =>
                        null,

                    'validation_status' =>
                        $validationStatus,

                    'error_messages' =>
                        $errors,

                    'warning_messages' =>
                        $warnings,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Row Progress
                |--------------------------------------------------------------------------
                |
                | Row validation occupies 20% through 80%.
                |
                */

                $processedRows =
                    $position
                    +
                    1;


                $rowProgressRatio =
                    $processedRows
                    /
                    max(
                        1,
                        $totalRows
                    );


                $progressPercentage =
                    20
                    +
                    (
                        $rowProgressRatio
                        *
                        60
                    );


                /*
                |--------------------------------------------------------------------------
                | Update Progress
                |--------------------------------------------------------------------------
                |
                | To avoid excessive UPDATE statements on very large files,
                | update at:
                |
                | - every row for small files
                | - approximately every 1% for large files
                | - always on the final row
                |
                */

                $progressUpdateInterval =
                    max(
                        1,
                        (int)
                        ceil(
                            $totalRows
                            /
                            100
                        )
                    );


                if (
                    $processedRows === 1
                    ||
                    $processedRows === $totalRows
                    ||
                    $processedRows % $progressUpdateInterval === 0
                ) {
                    $batch->update([
                        'processed_rows' =>
                            $processedRows,

                        'valid_rows' =>
                            $validRows,

                        'warning_rows' =>
                            $warningRows,

                        'error_rows' =>
                            $errorRows,

                        'existing_member_rows' =>
                            $existingMemberRows,

                        'new_member_rows' =>
                            $newMemberRows,

                        'progress_percentage' =>
                            round(
                                min(
                                    80,
                                    $progressPercentage
                                ),
                                2
                            ),
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | Stage 6 - Row Validation Complete
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'processed_rows' =>
                    $totalRows,

                'valid_rows' =>
                    $validRows,

                'warning_rows' =>
                    $warningRows,

                'error_rows' =>
                    $errorRows,

                'existing_member_rows' =>
                    $existingMemberRows,

                'new_member_rows' =>
                    $newMemberRows,

                'progress_percentage' =>
                    82,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 7 - Identify Nil Contributors
            |--------------------------------------------------------------------------
            |
            | Existing active members under the employer who are NOT on this
            | month's schedule become NIL CONTRIBUTORS.
            |
            | They are NOT exited.
            |
            */

            $nilContributorCount =
                $this->identifyNilContributors(
                    $batch,
                    array_values(
                        array_unique(
                            $scheduledMemberIds
                        )
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Nil Contributor Processing Complete
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'nil_contributor_rows' =>
                    $nilContributorCount,

                'progress_percentage' =>
                    90,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 8 - Calculate Contribution Period Summary
            |--------------------------------------------------------------------------
            */

            $uniqueScheduledMembers =
                count(
                    array_unique(
                        $scheduledMemberIds
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Stage 9 - Save Batch Totals
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | Do NOT set status to awaiting_review yet.
            | Do NOT set progress to 100 yet.
            |
            | There is still final database work to complete.
            |
            */

            $batch->update([
                'total_rows' =>
                    $totalRows,

                'processed_rows' =>
                    $totalRows,

                'valid_rows' =>
                    $validRows,

                'warning_rows' =>
                    $warningRows,

                'error_rows' =>
                    $errorRows,

                'existing_member_rows' =>
                    $existingMemberRows,

                'new_member_rows' =>
                    $newMemberRows,

                'nil_contributor_rows' =>
                    $nilContributorCount,


                /*
                |--------------------------------------------------------------------------
                | USD Totals
                |--------------------------------------------------------------------------
                */

                'usd_basic_pay_total' =>
                    $totals[
                        'usd_basic_pay_total'
                    ],

                'usd_employee_contribution_total' =>
                    $totals[
                        'usd_employee_contribution_total'
                    ],

                'usd_employer_contribution_total' =>
                    $totals[
                        'usd_employer_contribution_total'
                    ],

                'usd_employee_avc_total' =>
                    $totals[
                        'usd_employee_avc_total'
                    ],

                'usd_employer_avc_total' =>
                    $totals[
                        'usd_employer_avc_total'
                    ],


                /*
                |--------------------------------------------------------------------------
                | ZWG Totals
                |--------------------------------------------------------------------------
                */

                'zwg_basic_pay_total' =>
                    $totals[
                        'zwg_basic_pay_total'
                    ],

                'zwg_employee_contribution_total' =>
                    $totals[
                        'zwg_employee_contribution_total'
                    ],

                'zwg_employer_contribution_total' =>
                    $totals[
                        'zwg_employer_contribution_total'
                    ],

                'zwg_employee_avc_total' =>
                    $totals[
                        'zwg_employee_avc_total'
                    ],

                'zwg_employer_avc_total' =>
                    $totals[
                        'zwg_employer_avc_total'
                    ],

                /*
                |--------------------------------------------------------------------------
                | Still Processing
                |--------------------------------------------------------------------------
                */

                'progress_percentage' =>
                    94,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 10 - Update Contribution Period
            |--------------------------------------------------------------------------
            */

            $batch
                ->contributionPeriod
                ->update([
                    'status' =>
                        'awaiting_review',

                    'scheduled_members' =>
                        $uniqueScheduledMembers
                        +
                        $newMemberRows,

                    'existing_members' =>
                        $uniqueScheduledMembers,

                    'new_members' =>
                        $newMemberRows,

                    'nil_contributors' =>
                        $nilContributorCount,

                    'updated_by' =>
                        $batch
                            ->uploaded_by,
                ]);


            /*
            |--------------------------------------------------------------------------
            | Contribution Period Successfully Updated
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'progress_percentage' =>
                    98,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 11 - Validation Completely Finished
            |--------------------------------------------------------------------------
            |
            | ONLY NOW:
            |
            | status = awaiting_review
            | progress = 100
            |
            | The UI can safely redirect to the Review page.
            |
            */

            $batch->update([
                'status' =>
                    'awaiting_review',

                'progress_percentage' =>
                    100,

                'completed_at' =>
                    now(),

                'failure_reason' =>
                    null,
            ]);

        } catch (Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | Failed Validation
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'status' =>
                    'failed',

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
    | Map Excel Financial Values To Currency
    |--------------------------------------------------------------------------
    */

    private function mapCurrencyValues(
        string $currency,
        array $data
    ): array {
        $mapped = [
            ...$data,

            /*
            |--------------------------------------------------------------------------
            | USD
            |--------------------------------------------------------------------------
            */

            'usd_basic_pay' =>
                (float) (
                    $data[
                        'usd_basic_pay'
                    ]
                    ?? 0
                ),

            'usd_employee_rate' =>
                (float) (
                    $data[
                        'usd_employee_rate'
                    ]
                    ?? 0
                ),

            'usd_employer_rate' =>
                (float) (
                    $data[
                        'usd_employer_rate'
                    ]
                    ?? 0
                ),

            'usd_employee_contribution' =>
                (float) (
                    $data[
                        'usd_employee_contribution'
                    ]
                    ?? 0
                ),

            'usd_employer_contribution' =>
                (float) (
                    $data[
                        'usd_employer_contribution'
                    ]
                    ?? 0
                ),

            'usd_employee_avc' =>
                (float) (
                    $data[
                        'usd_employee_avc'
                    ]
                    ?? 0
                ),

            'usd_employer_avc' =>
                (float) (
                    $data[
                        'usd_employer_avc'
                    ]
                    ?? 0
                ),

            'usd_employee_arrear' =>
                (float) (
                    $data[
                        'usd_employee_arrear'
                    ]
                    ?? 0
                ),

            'usd_employer_arrear' =>
                (float) (
                    $data[
                        'usd_employer_arrear'
                    ]
                    ?? 0
                ),

            'usd_employee_transfer_in' =>
                (float) (
                    $data[
                        'usd_employee_transfer_in'
                    ]
                    ?? 0
                ),

            'usd_employer_transfer_in' =>
                (float) (
                    $data[
                        'usd_employer_transfer_in'
                    ]
                    ?? 0
                ),

            'usd_employee_late_interest' =>
                (float) (
                    $data[
                        'usd_employee_late_interest'
                    ]
                    ?? 0
                ),

            'usd_employer_late_interest' =>
                (float) (
                    $data[
                        'usd_employer_late_interest'
                    ]
                    ?? 0
                ),


            /*
            |--------------------------------------------------------------------------
            | ZWG
            |--------------------------------------------------------------------------
            */

            'zwg_basic_pay' =>
                (float) (
                    $data[
                        'zwg_basic_pay'
                    ]
                    ?? 0
                ),

            'zwg_employee_rate' =>
                (float) (
                    $data[
                        'zwg_employee_rate'
                    ]
                    ?? 0
                ),

            'zwg_employer_rate' =>
                (float) (
                    $data[
                        'zwg_employer_rate'
                    ]
                    ?? 0
                ),

            'zwg_employee_contribution' =>
                (float) (
                    $data[
                        'zwg_employee_contribution'
                    ]
                    ?? 0
                ),

            'zwg_employer_contribution' =>
                (float) (
                    $data[
                        'zwg_employer_contribution'
                    ]
                    ?? 0
                ),

            'zwg_employee_avc' =>
                (float) (
                    $data[
                        'zwg_employee_avc'
                    ]
                    ?? 0
                ),

            'zwg_employer_avc' =>
                (float) (
                    $data[
                        'zwg_employer_avc'
                    ]
                    ?? 0
                ),

            'zwg_employee_arrear' =>
                (float) (
                    $data[
                        'zwg_employee_arrear'
                    ]
                    ?? 0
                ),

            'zwg_employer_arrear' =>
                (float) (
                    $data[
                        'zwg_employer_arrear'
                    ]
                    ?? 0
                ),

            'zwg_employee_transfer_in' =>
                (float) (
                    $data[
                        'zwg_employee_transfer_in'
                    ]
                    ?? 0
                ),

            'zwg_employer_transfer_in' =>
                (float) (
                    $data[
                        'zwg_employer_transfer_in'
                    ]
                    ?? 0
                ),

            'zwg_employee_late_interest' =>
                (float) (
                    $data[
                        'zwg_employee_late_interest'
                    ]
                    ?? 0
                ),

            'zwg_employer_late_interest' =>
                (float) (
                    $data[
                        'zwg_employer_late_interest'
                    ]
                    ?? 0
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | Neutral Template Values
        |--------------------------------------------------------------------------
        */

        $basicPay =
            (float) (
                $data[
                    'basic_pay'
                ]
                ?? 0
            );


        $employeeRate =
            (float) (
                $data[
                    'employee_rate'
                ]
                ?? 0
            );


        $employerRate =
            (float) (
                $data[
                    'employer_rate'
                ]
                ?? 0
            );


        $employeeContribution =
            (float) (
                $data[
                    'employee_contribution'
                ]
                ?? 0
            );


        $employerContribution =
            (float) (
                $data[
                    'employer_contribution'
                ]
                ?? 0
            );


        $employeeAvc =
            (float) (
                $data[
                    'employee_avc'
                ]
                ?? 0
            );


        $employerAvc =
            (float) (
                $data[
                    'employer_avc'
                ]
                ?? 0
            );


        $employeeArrear =
            (float) (
                $data[
                    'employee_arrear'
                ]
                ?? 0
            );


        $employerArrear =
            (float) (
                $data[
                    'employer_arrear'
                ]
                ?? 0
            );


        $employeeTransferIn =
            (float) (
                $data[
                    'employee_transfer_in'
                ]
                ?? 0
            );


        $employerTransferIn =
            (float) (
                $data[
                    'employer_transfer_in'
                ]
                ?? 0
            );


        $employeeLateInterest =
            (float) (
                $data[
                    'employee_late_interest'
                ]
                ?? 0
            );


        $employerLateInterest =
            (float) (
                $data[
                    'employer_late_interest'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | ZWG Schedule
        |--------------------------------------------------------------------------
        */

        if (
            $currency ===
            'ZWG'
        ) {

            if (
                array_key_exists(
                    'basic_pay',
                    $data
                )
            ) {
                $mapped[
                    'zwg_basic_pay'
                ] =
                    $basicPay;
            }


            if (
                array_key_exists(
                    'employee_rate',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_rate'
                ] =
                    $employeeRate;
            }


            if (
                array_key_exists(
                    'employer_rate',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_rate'
                ] =
                    $employerRate;
            }


            if (
                array_key_exists(
                    'employee_contribution',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_contribution'
                ] =
                    $employeeContribution;
            }


            if (
                array_key_exists(
                    'employer_contribution',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_contribution'
                ] =
                    $employerContribution;
            }


            if (
                array_key_exists(
                    'employee_avc',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_avc'
                ] =
                    $employeeAvc;
            }


            if (
                array_key_exists(
                    'employer_avc',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_avc'
                ] =
                    $employerAvc;
            }


            if (
                array_key_exists(
                    'employee_arrear',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_arrear'
                ] =
                    $employeeArrear;
            }


            if (
                array_key_exists(
                    'employer_arrear',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_arrear'
                ] =
                    $employerArrear;
            }


            if (
                array_key_exists(
                    'employee_transfer_in',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_transfer_in'
                ] =
                    $employeeTransferIn;
            }


            if (
                array_key_exists(
                    'employer_transfer_in',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_transfer_in'
                ] =
                    $employerTransferIn;
            }


            if (
                array_key_exists(
                    'employee_late_interest',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employee_late_interest'
                ] =
                    $employeeLateInterest;
            }


            if (
                array_key_exists(
                    'employer_late_interest',
                    $data
                )
            ) {
                $mapped[
                    'zwg_employer_late_interest'
                ] =
                    $employerLateInterest;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | USD Schedule
        |--------------------------------------------------------------------------
        */

        if (
            $currency ===
            'USD'
        ) {

            if (
                array_key_exists(
                    'basic_pay',
                    $data
                )
            ) {
                $mapped[
                    'usd_basic_pay'
                ] =
                    $basicPay;
            }


            if (
                array_key_exists(
                    'employee_rate',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_rate'
                ] =
                    $employeeRate;
            }


            if (
                array_key_exists(
                    'employer_rate',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_rate'
                ] =
                    $employerRate;
            }


            if (
                array_key_exists(
                    'employee_contribution',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_contribution'
                ] =
                    $employeeContribution;
            }


            if (
                array_key_exists(
                    'employer_contribution',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_contribution'
                ] =
                    $employerContribution;
            }


            if (
                array_key_exists(
                    'employee_avc',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_avc'
                ] =
                    $employeeAvc;
            }


            if (
                array_key_exists(
                    'employer_avc',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_avc'
                ] =
                    $employerAvc;
            }


            if (
                array_key_exists(
                    'employee_arrear',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_arrear'
                ] =
                    $employeeArrear;
            }


            if (
                array_key_exists(
                    'employer_arrear',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_arrear'
                ] =
                    $employerArrear;
            }


            if (
                array_key_exists(
                    'employee_transfer_in',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_transfer_in'
                ] =
                    $employeeTransferIn;
            }


            if (
                array_key_exists(
                    'employer_transfer_in',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_transfer_in'
                ] =
                    $employerTransferIn;
            }


            if (
                array_key_exists(
                    'employee_late_interest',
                    $data
                )
            ) {
                $mapped[
                    'usd_employee_late_interest'
                ] =
                    $employeeLateInterest;
            }


            if (
                array_key_exists(
                    'employer_late_interest',
                    $data
                )
            ) {
                $mapped[
                    'usd_employer_late_interest'
                ] =
                    $employerLateInterest;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Currency With Normalised Row
        |--------------------------------------------------------------------------
        */

        $mapped[
            'currency_code'
        ] =
            $currency;


        return $mapped;
    }


    /*
    |--------------------------------------------------------------------------
    | Required Member Data
    |--------------------------------------------------------------------------
    */

    private function validateRequiredMemberData(
        array $data,
        array &$errors
    ): void {
        if (
            blank(
                $data[
                    'surname'
                ]
                ?? null
            )
        ) {
            $errors[] =
                'Surname is missing.';
        }


        if (
            blank(
                $data[
                    'first_names'
                ]
                ?? null
            )
        ) {
            $errors[] =
                'First name is missing.';
        }


        /*
        |--------------------------------------------------------------------------
        | At Least One Identifier
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data[
                    'pension_reference_number'
                ]
                ?? null
            )
            &&
            blank(
                $data[
                    'penerp_member_number'
                ]
                ?? null
            )
            &&
            blank(
                $data[
                    'staff_number'
                ]
                ?? null
            )
            &&
            blank(
                $data[
                    'national_id'
                ]
                ?? null
            )
        ) {
            $errors[] =
                'No PenAd/PENSION reference number, PENERP member number, staff number or National ID was supplied.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Reference Validation
    |--------------------------------------------------------------------------
    */

    private function validateEmployerReference(
        ContributionImportBatch $batch,
        array $data,
        array &$errors
    ): void {
        if (
            blank(
                $data[
                    'employer_number'
                ]
                ?? null
            )
        ) {
            return;
        }


        $excelEmployer =
            strtoupper(
                trim(
                    (string)
                    $data[
                        'employer_number'
                    ]
                )
            );


        $validEmployerNumbers =
            collect([
                $batch
                    ->employer
                    ->employer_number
                    ?? null,

                $batch
                    ->employer
                    ->penad_employer_number
                    ?? null,

                $batch
                    ->employer
                    ->fundworx_employer_number
                    ?? null,
            ])
                ->filter(
                    fn ($value) =>
                        filled(
                            $value
                        )
                )
                ->map(
                    fn ($value) =>
                        strtoupper(
                            trim(
                                (string)
                                $value
                            )
                        )
                );


        if (
            $validEmployerNumbers
                ->isNotEmpty()
            &&
            !$validEmployerNumbers
                ->contains(
                    $excelEmployer
                )
        ) {
            $errors[] =
                'The employer number '
                . $excelEmployer
                . ' in the Excel row does not match the employer selected for this upload.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Contribution Period Validation
    |--------------------------------------------------------------------------
    */

    private function validatePeriod(
        ContributionImportBatch $batch,
        array $data,
        array &$warnings
    ): void {
        if (
            blank(
                $data[
                    'due_date'
                ]
                ?? null
            )
        ) {
            return;
        }


        try {

            $rowDate =
                Carbon::parse(
                    $data[
                        'due_date'
                    ]
                );


            if (
                $rowDate->year
                !==
                (int)
                $batch
                    ->contributionPeriod
                    ->period_year
                ||
                $rowDate->month
                !==
                (int)
                $batch
                    ->contributionPeriod
                    ->period_month
            ) {
                $warnings[] =
                    'The Excel due date '
                    . $rowDate->format(
                        'd M Y'
                    )
                    . ' is not in the selected contribution month '
                    . $batch
                        ->contributionPeriod
                        ->period_label
                    . '.';
            }

        } catch (Throwable) {

            $warnings[] =
                'The due date supplied in this row could not be interpreted.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Financial Validation
    |--------------------------------------------------------------------------
    */

    private function validateFinancialValues(
        string $currency,
        array $data,
        array &$warnings
    ): void {
        if (
            $currency ===
            'USD'
        ) {

            $basicPay =
                (float) (
                    $data[
                        'usd_basic_pay'
                    ]
                    ?? 0
                );


            $employeeContribution =
                (float) (
                    $data[
                        'usd_employee_contribution'
                    ]
                    ?? 0
                );


            $employerContribution =
                (float) (
                    $data[
                        'usd_employer_contribution'
                    ]
                    ?? 0
                );


            $employeeAvc =
                (float) (
                    $data[
                        'usd_employee_avc'
                    ]
                    ?? 0
                );


            $employerAvc =
                (float) (
                    $data[
                        'usd_employer_avc'
                    ]
                    ?? 0
                );


            $employeeArrear =
                (float) (
                    $data[
                        'usd_employee_arrear'
                    ]
                    ?? 0
                );


            $employerArrear =
                (float) (
                    $data[
                        'usd_employer_arrear'
                    ]
                    ?? 0
                );

        } else {

            $basicPay =
                (float) (
                    $data[
                        'zwg_basic_pay'
                    ]
                    ?? 0
                );


            $employeeContribution =
                (float) (
                    $data[
                        'zwg_employee_contribution'
                    ]
                    ?? 0
                );


            $employerContribution =
                (float) (
                    $data[
                        'zwg_employer_contribution'
                    ]
                    ?? 0
                );


            $employeeAvc =
                (float) (
                    $data[
                        'zwg_employee_avc'
                    ]
                    ?? 0
                );


            $employerAvc =
                (float) (
                    $data[
                        'zwg_employer_avc'
                    ]
                    ?? 0
                );


            $employeeArrear =
                (float) (
                    $data[
                        'zwg_employee_arrear'
                    ]
                    ?? 0
                );


            $employerArrear =
                (float) (
                    $data[
                        'zwg_employer_arrear'
                    ]
                    ?? 0
                );
        }


        $financialValues = [
            $employeeContribution,
            $employerContribution,
            $employeeAvc,
            $employerAvc,
            $employeeArrear,
            $employerArrear,
        ];


        /*
        |--------------------------------------------------------------------------
        | Negative Contributions
        |--------------------------------------------------------------------------
        */

        if (
            collect(
                $financialValues
            )
                ->contains(
                    fn ($amount) =>
                        (float)
                        $amount
                        <
                        0
                )
        ) {
            $warnings[] =
                'Negative '
                . $currency
                . ' expected contribution detected. This row will require review and will later be posted as an adjustment.';
        }


        /*
        |--------------------------------------------------------------------------
        | Zero Contribution
        |--------------------------------------------------------------------------
        */

        $allZero =
            collect(
                $financialValues
            )
                ->every(
                    fn ($amount) =>
                        (float)
                        $amount
                        ===
                        0.0
                );


        if ($allZero) {
            $warnings[] =
                'All '
                . $currency
                . ' contribution amounts are zero.';
        }


        /*
        |--------------------------------------------------------------------------
        | Salary Exists But No Contribution
        |--------------------------------------------------------------------------
        */

        if (
            $basicPay > 0
            &&
            $allZero
        ) {
            $warnings[] =
                $currency
                . ' pensionable salary exists but all contribution amounts are zero.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Member Employer Validation
    |--------------------------------------------------------------------------
    */

    private function validateExistingMemberEmployer(
        ContributionImportBatch $batch,
        Member $member,
        array &$warnings
    ): void {
        $currentEmployment =
            MemberEmployment::query()
                ->where(
                    'member_id',
                    $member->id
                )
                ->where(
                    'is_current',
                    true
                )
                ->first();


        if (!$currentEmployment) {
            $warnings[] =
                'The matched member does not currently have an active employer relationship in PENERP.';

            return;
        }


        if (
            (int)
            $currentEmployment
                ->employer_id
            !==
            (int)
            $batch
                ->employer_id
        ) {
            $warnings[] =
                'The matched member currently belongs to another employer in PENERP.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Member Identity Validation
    |--------------------------------------------------------------------------
    */

    private function validateExistingMemberIdentity(
        Member $member,
        array $data,
        array &$warnings
    ): void {
        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $excelNationalId =
            Member::normalizeNationalId(
                $data[
                    'national_id'
                ]
                ?? null
            );


        if (
            $excelNationalId
            &&
            $member
                ->national_id_normalized
            &&
            $excelNationalId
            !==
            $member
                ->national_id_normalized
        ) {
            $warnings[] =
                'The National ID on the contribution schedule differs from the National ID stored against the matched member.';
        }


        /*
        |--------------------------------------------------------------------------
        | Surname
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'surname'
                ]
                ?? null
            )
            &&
            strtoupper(
                trim(
                    (string)
                    $data[
                        'surname'
                    ]
                )
            )
            !==
            strtoupper(
                trim(
                    (string)
                    $member
                        ->surname
                )
            )
        ) {
            $warnings[] =
                'The surname on the contribution schedule differs from the surname stored against the matched member.';
        }


        /*
        |--------------------------------------------------------------------------
        | First Names
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'first_names'
                ]
                ?? null
            )
            &&
            filled(
                $member
                    ->first_names
            )
            &&
            strtoupper(
                trim(
                    (string)
                    $data[
                        'first_names'
                    ]
                )
            )
            !==
            strtoupper(
                trim(
                    (string)
                    $member
                        ->first_names
                )
            )
        ) {
            $warnings[] =
                'The first name(s) on the contribution schedule differ from the name(s) stored against the matched member.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | New Member Candidate Validation
    |--------------------------------------------------------------------------
    */

    private function validateNewMemberCandidate(
        ContributionImportBatch $batch,
        array $data,
        array &$errors,
        array &$warnings
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        $staffNumber =
            trim(
                (string) (
                    $data[
                        'staff_number'
                    ]
                    ?? ''
                )
            );


        if (
            $staffNumber ===
            ''
        ) {
            $errors[] =
                'Staff Number is required for a proposed new member.';

        } else {

            $staffNumberAlreadyExists =
                MemberEmployment::query()
                    ->where(
                        'employer_id',
                        $batch->employer_id
                    )
                    ->where(
                        'staff_number',
                        $staffNumber
                    )
                    ->where(
                        'is_current',
                        true
                    )
                    ->exists();


            if (
                $staffNumberAlreadyExists
            ) {
                $errors[] =
                    'Staff number '
                    . $staffNumber
                    . ' is already assigned to a current member under this employer.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Date Joined Fund
        |--------------------------------------------------------------------------
        |
        | REQUIRED FOR NEW MEMBERS.
        |
        */

        if (
            blank(
                $data[
                    'date_joined_fund'
                ]
                ?? null
            )
        ) {
            $errors[] =
                'Date Joined Fund is required for a proposed new member.';
        }


        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $normalizedNationalId =
            Member::normalizeNationalId(
                $data[
                    'national_id'
                ]
                ?? null
            );


        if (!$normalizedNationalId) {

            $warnings[] =
                'National ID is missing for the proposed new member.';

        } else {

            $nationalIdExists =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $normalizedNationalId
                    )
                    ->exists();


            if ($nationalIdExists) {

                $errors[] =
                    'National ID '
                    . (
                        $data[
                            'national_id'
                        ]
                        ?? ''
                    )
                    . ' already belongs to an existing PENERP member.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Date Of Birth
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data[
                    'date_of_birth'
                ]
                ?? null
            )
        ) {
            $warnings[] =
                'Date of birth is missing for the proposed new member.';
        }


        /*
        |--------------------------------------------------------------------------
        | Member Number
        |--------------------------------------------------------------------------
        |
        | A genuinely new member is NOT required to already have a PENERP
        | or PenAd number.
        |
        */

        if (
            blank(
                $data[
                    'penerp_member_number'
                ]
                ?? null
            )
            &&
            blank(
                $data[
                    'penad_member_number'
                ]
                ?? null
            )
            &&
            blank(
                $data[
                    'pension_reference_number'
                ]
                ?? null
            )
        ) {
            $warnings[] =
                'No existing PENERP/PenAd member number was supplied. A new member number will be allocated automatically when this batch is posted.';
        }


        /*
        |--------------------------------------------------------------------------
        | Classification
        |--------------------------------------------------------------------------
        */

        $warnings[] =
            'No existing member matched this row. It has been classified as a proposed new member and has not yet been permanently created.';
    }


    /*
    |--------------------------------------------------------------------------
    | Identify Nil Contributors
    |--------------------------------------------------------------------------
    */

    private function identifyNilContributors(
        ContributionImportBatch $batch,
        array $scheduledMemberIds
    ): int {
        /*
        |--------------------------------------------------------------------------
        | Current Members Under Employer
        |--------------------------------------------------------------------------
        */

        $memberIds =
            MemberEmployment::query()
                ->where(
                    'employer_id',
                    $batch
                        ->employer_id
                )
                ->where(
                    'is_current',
                    true
                )
                ->pluck(
                    'member_id'
                )
                ->unique();


        /*
        |--------------------------------------------------------------------------
        | Remove Members Present On Schedule
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $scheduledMemberIds
            )
        ) {
            $memberIds =
                $memberIds->diff(
                    $scheduledMemberIds
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Active Members Only
        |--------------------------------------------------------------------------
        */

        $members =
            Member::query()
                ->whereIn(
                    'id',
                    $memberIds
                )
                ->where(
                    'is_active',
                    true
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | Create Monthly NIL Status
        |--------------------------------------------------------------------------
        */

        foreach (
            $members
            as $member
        ) {

            ContributionPeriodMemberStatus::updateOrCreate(
                [
                    'contribution_period_id' =>
                        $batch
                            ->contribution_period_id,

                    'member_id' =>
                        $member
                            ->id,
                ],
                [
                    'employer_id' =>
                        $batch
                            ->employer_id,

                    'contribution_status' =>
                        'nil_contributor',

                    'reason' =>
                        'Active member did not appear on the expected contribution schedule for this contribution period.',

                    'import_batch_id' =>
                        $batch
                            ->id,
                ]
            );
        }


        return $members->count();
    }


    /*
    |--------------------------------------------------------------------------
    | Duplicate Fingerprint
    |--------------------------------------------------------------------------
    */

    private function makeFingerprint(
        array $data
    ): string {
        return hash(
            'sha256',
            implode(
                '|',
                [
                    strtoupper(
                        trim(
                            (string) (
                                $data[
                                    'pension_reference_number'
                                ]
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data[
                                    'penerp_member_number'
                                ]
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data[
                                    'staff_number'
                                ]
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        Member::normalizeNationalId(
                            $data[
                                'national_id'
                            ]
                            ?? null
                        )
                        ??
                        ''
                    ),
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Add Batch Totals
    |--------------------------------------------------------------------------
    */

    private function addTotals(
        array &$totals,
        array $data
    ): void {
        /*
        |--------------------------------------------------------------------------
        | USD
        |--------------------------------------------------------------------------
        */

        $totals[
            'usd_basic_pay_total'
        ] +=
            (float) (
                $data[
                    'usd_basic_pay'
                ]
                ?? 0
            );


        $totals[
            'usd_employee_contribution_total'
        ] +=
            (float) (
                $data[
                    'usd_employee_contribution'
                ]
                ?? 0
            );


        $totals[
            'usd_employer_contribution_total'
        ] +=
            (float) (
                $data[
                    'usd_employer_contribution'
                ]
                ?? 0
            );


        $totals[
            'usd_employee_avc_total'
        ] +=
            (float) (
                $data[
                    'usd_employee_avc'
                ]
                ?? 0
            );


        $totals[
            'usd_employer_avc_total'
        ] +=
            (float) (
                $data[
                    'usd_employer_avc'
                ]
                ?? 0
            );


        /*
        |--------------------------------------------------------------------------
        | ZWG
        |--------------------------------------------------------------------------
        */

        $totals[
            'zwg_basic_pay_total'
        ] +=
            (float) (
                $data[
                    'zwg_basic_pay'
                ]
                ?? 0
            );


        $totals[
            'zwg_employee_contribution_total'
        ] +=
            (float) (
                $data[
                    'zwg_employee_contribution'
                ]
                ?? 0
            );


        $totals[
            'zwg_employer_contribution_total'
        ] +=
            (float) (
                $data[
                    'zwg_employer_contribution'
                ]
                ?? 0
            );


        $totals[
            'zwg_employee_avc_total'
        ] +=
            (float) (
                $data[
                    'zwg_employee_avc'
                ]
                ?? 0
            );


        $totals[
            'zwg_employer_avc_total'
        ] +=
            (float) (
                $data[
                    'zwg_employer_avc'
                ]
                ?? 0
            );
    }
}