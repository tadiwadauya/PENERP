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

        if (!$batch->contributionPeriod) {
            throw new RuntimeException(
                'The contribution batch does not have a valid contribution period.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        |
        | PENERP base currency = ZWG.
        |
        | Supported upload currencies:
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
                        ??
                        'ZWG'
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


            $batch->update([
                'progress_percentage' =>
                    8,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Read Excel
            |--------------------------------------------------------------------------
            */

            $excel =
                $this
                    ->excelReader
                    ->read(
                        $fullPath
                    );


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
                ??
                [];


            $totalRows =
                count(
                    $excelRows
                );


            if ($totalRows === 0) {
                throw new RuntimeException(
                    'The contribution Excel file does not contain any contribution rows.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Store Total Rows Early
            |--------------------------------------------------------------------------
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
            | Duplicate Detection
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
                    ??
                    [];


                /*
                |--------------------------------------------------------------------------
                | Map Generic Values To Selected Currency
                |--------------------------------------------------------------------------
                */

                $data =
                    $this->mapCurrencyValues(
                        $currency,
                        $data
                    );


                /*
                |--------------------------------------------------------------------------
                | Row Messages
                |--------------------------------------------------------------------------
                */

                $errors =
                    [];

                $warnings =
                    [];


                /*
                |--------------------------------------------------------------------------
                | Required Basic Member Information
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
                | Period
                |--------------------------------------------------------------------------
                */

                $this->validatePeriod(
                    $batch,
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
                        ??
                        (
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
                | Priority:
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
                    ??
                    null;


                $matchType =
                    $match[
                        'match_type'
                    ]
                    ??
                    null;


                /*
                |--------------------------------------------------------------------------
                | IMPORTANT
                |--------------------------------------------------------------------------
                |
                | Determine new/existing member BEFORE rate validation.
                |
                */

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
                    ??
                    false
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
                    | Existing Member Employer
                    |--------------------------------------------------------------------------
                    */

                    $this->validateExistingMemberEmployer(
                        $batch,
                        $member,
                        $warnings
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Existing Member Identity
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
                        ??
                        false
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
                    | Count Postable New Member Candidate
                    |--------------------------------------------------------------------------
                    |
                    | Rows containing hard errors are not counted as new members
                    | ready for posting.
                    |
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
                | Financial Validation
                |--------------------------------------------------------------------------
                |
                | IMPORTANT:
                |
                | This MUST happen after member matching because:
                |
                | New member employee rate      = 6%
                | Existing member employee rate = 5% to 6%
                | Employer rate                 = 17.3%
                |
                | Rate/calculation mismatches are WARNINGS.
                |
                */

                $this->validateFinancialValues(
                    currency:
                        $currency,

                    data:
                        $data,

                    isNewMember:
                        $isNewMember,

                    warnings:
                        $warnings
                );


                /*
                |--------------------------------------------------------------------------
                | Remove Duplicate Messages
                |--------------------------------------------------------------------------
                */

                $errors =
                    array_values(
                        array_unique(
                            $errors
                        )
                    );


                $warnings =
                    array_values(
                        array_unique(
                            $warnings
                        )
                    );


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
                | Batch Financial Totals
                |--------------------------------------------------------------------------
                |
                | These represent the actual uploaded schedule.
                |
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
                        ??
                        (
                            $position
                            +
                            2
                        ),

                    'raw_data' =>
                        $excelRow[
                            'raw_data'
                        ]
                        ??
                        [],

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
                | Reduce Progress UPDATE Statements
                |--------------------------------------------------------------------------
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
            | Existing active members under the employer who do not appear on
            | the current schedule become NIL CONTRIBUTORS.
            |
            | They are not exited.
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


            $batch->update([
                'nil_contributor_rows' =>
                    $nilContributorCount,

                'progress_percentage' =>
                    90,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 8 - Contribution Period Summary
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


            $batch->update([
                'progress_percentage' =>
                    98,
            ]);


            /*
            |--------------------------------------------------------------------------
            | Stage 11 - Validation Complete
            |--------------------------------------------------------------------------
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
        /*
        |--------------------------------------------------------------------------
        | Start With Existing Legacy Values
        |--------------------------------------------------------------------------
        */

        $mapped = [
            ...$data,

            'usd_basic_pay' =>
                (float) (
                    $data[
                        'usd_basic_pay'
                    ]
                    ??
                    0
                ),

            'usd_employee_rate' =>
                $this->normalisePercentageRate(
                    $data[
                        'usd_employee_rate'
                    ]
                    ??
                    0
                ),

            'usd_employer_rate' =>
                $this->normalisePercentageRate(
                    $data[
                        'usd_employer_rate'
                    ]
                    ??
                    0
                ),

            'usd_employee_contribution' =>
                (float) (
                    $data[
                        'usd_employee_contribution'
                    ]
                    ??
                    0
                ),

            'usd_employer_contribution' =>
                (float) (
                    $data[
                        'usd_employer_contribution'
                    ]
                    ??
                    0
                ),

            'usd_employee_avc' =>
                (float) (
                    $data[
                        'usd_employee_avc'
                    ]
                    ??
                    0
                ),

            'usd_employer_avc' =>
                (float) (
                    $data[
                        'usd_employer_avc'
                    ]
                    ??
                    0
                ),

            'usd_employee_arrear' =>
                (float) (
                    $data[
                        'usd_employee_arrear'
                    ]
                    ??
                    0
                ),

            'usd_employer_arrear' =>
                (float) (
                    $data[
                        'usd_employer_arrear'
                    ]
                    ??
                    0
                ),

            'usd_employee_transfer_in' =>
                (float) (
                    $data[
                        'usd_employee_transfer_in'
                    ]
                    ??
                    0
                ),

            'usd_employer_transfer_in' =>
                (float) (
                    $data[
                        'usd_employer_transfer_in'
                    ]
                    ??
                    0
                ),

            'usd_employee_late_interest' =>
                (float) (
                    $data[
                        'usd_employee_late_interest'
                    ]
                    ??
                    0
                ),

            'usd_employer_late_interest' =>
                (float) (
                    $data[
                        'usd_employer_late_interest'
                    ]
                    ??
                    0
                ),


            'zwg_basic_pay' =>
                (float) (
                    $data[
                        'zwg_basic_pay'
                    ]
                    ??
                    0
                ),

            'zwg_employee_rate' =>
                $this->normalisePercentageRate(
                    $data[
                        'zwg_employee_rate'
                    ]
                    ??
                    0
                ),

            'zwg_employer_rate' =>
                $this->normalisePercentageRate(
                    $data[
                        'zwg_employer_rate'
                    ]
                    ??
                    0
                ),

            'zwg_employee_contribution' =>
                (float) (
                    $data[
                        'zwg_employee_contribution'
                    ]
                    ??
                    0
                ),

            'zwg_employer_contribution' =>
                (float) (
                    $data[
                        'zwg_employer_contribution'
                    ]
                    ??
                    0
                ),

            'zwg_employee_avc' =>
                (float) (
                    $data[
                        'zwg_employee_avc'
                    ]
                    ??
                    0
                ),

            'zwg_employer_avc' =>
                (float) (
                    $data[
                        'zwg_employer_avc'
                    ]
                    ??
                    0
                ),

            'zwg_employee_arrear' =>
                (float) (
                    $data[
                        'zwg_employee_arrear'
                    ]
                    ??
                    0
                ),

            'zwg_employer_arrear' =>
                (float) (
                    $data[
                        'zwg_employer_arrear'
                    ]
                    ??
                    0
                ),

            'zwg_employee_transfer_in' =>
                (float) (
                    $data[
                        'zwg_employee_transfer_in'
                    ]
                    ??
                    0
                ),

            'zwg_employer_transfer_in' =>
                (float) (
                    $data[
                        'zwg_employer_transfer_in'
                    ]
                    ??
                    0
                ),

            'zwg_employee_late_interest' =>
                (float) (
                    $data[
                        'zwg_employee_late_interest'
                    ]
                    ??
                    0
                ),

            'zwg_employer_late_interest' =>
                (float) (
                    $data[
                        'zwg_employer_late_interest'
                    ]
                    ??
                    0
                ),
        ];


        /*
        |--------------------------------------------------------------------------
        | Generic Template Values
        |--------------------------------------------------------------------------
        */

        $basicPay =
            (float) (
                $data[
                    'basic_pay'
                ]
                ??
                0
            );


        $employeeRate =
            $this->normalisePercentageRate(
                $data[
                    'employee_rate'
                ]
                ??
                0
            );


        $employerRate =
            $this->normalisePercentageRate(
                $data[
                    'employer_rate'
                ]
                ??
                0
            );


        $employeeContribution =
            (float) (
                $data[
                    'employee_contribution'
                ]
                ??
                0
            );


        $employerContribution =
            (float) (
                $data[
                    'employer_contribution'
                ]
                ??
                0
            );


        $employeeAvc =
            (float) (
                $data[
                    'employee_avc'
                ]
                ??
                0
            );


        $employerAvc =
            (float) (
                $data[
                    'employer_avc'
                ]
                ??
                0
            );


        $employeeArrear =
            (float) (
                $data[
                    'employee_arrear'
                ]
                ??
                0
            );


        $employerArrear =
            (float) (
                $data[
                    'employer_arrear'
                ]
                ??
                0
            );


        $employeeTransferIn =
            (float) (
                $data[
                    'employee_transfer_in'
                ]
                ??
                0
            );


        $employerTransferIn =
            (float) (
                $data[
                    'employer_transfer_in'
                ]
                ??
                0
            );


        $employeeLateInterest =
            (float) (
                $data[
                    'employee_late_interest'
                ]
                ??
                0
            );


        $employerLateInterest =
            (float) (
                $data[
                    'employer_late_interest'
                ]
                ??
                0
            );


        /*
        |--------------------------------------------------------------------------
        | ZWG
        |--------------------------------------------------------------------------
        */

        if ($currency === 'ZWG') {
            $mapped[
                'zwg_basic_pay'
            ] =
                $basicPay;


            $mapped[
                'zwg_employee_rate'
            ] =
                $employeeRate;


            $mapped[
                'zwg_employer_rate'
            ] =
                $employerRate;


            $mapped[
                'zwg_employee_contribution'
            ] =
                $employeeContribution;


            $mapped[
                'zwg_employer_contribution'
            ] =
                $employerContribution;


            $mapped[
                'zwg_employee_avc'
            ] =
                $employeeAvc;


            $mapped[
                'zwg_employer_avc'
            ] =
                $employerAvc;


            $mapped[
                'zwg_employee_arrear'
            ] =
                $employeeArrear;


            $mapped[
                'zwg_employer_arrear'
            ] =
                $employerArrear;


            $mapped[
                'zwg_employee_transfer_in'
            ] =
                $employeeTransferIn;


            $mapped[
                'zwg_employer_transfer_in'
            ] =
                $employerTransferIn;


            $mapped[
                'zwg_employee_late_interest'
            ] =
                $employeeLateInterest;


            $mapped[
                'zwg_employer_late_interest'
            ] =
                $employerLateInterest;
        }


        /*
        |--------------------------------------------------------------------------
        | USD
        |--------------------------------------------------------------------------
        */

        if ($currency === 'USD') {
            $mapped[
                'usd_basic_pay'
            ] =
                $basicPay;


            $mapped[
                'usd_employee_rate'
            ] =
                $employeeRate;


            $mapped[
                'usd_employer_rate'
            ] =
                $employerRate;


            $mapped[
                'usd_employee_contribution'
            ] =
                $employeeContribution;


            $mapped[
                'usd_employer_contribution'
            ] =
                $employerContribution;


            $mapped[
                'usd_employee_avc'
            ] =
                $employeeAvc;


            $mapped[
                'usd_employer_avc'
            ] =
                $employerAvc;


            $mapped[
                'usd_employee_arrear'
            ] =
                $employeeArrear;


            $mapped[
                'usd_employer_arrear'
            ] =
                $employerArrear;


            $mapped[
                'usd_employee_transfer_in'
            ] =
                $employeeTransferIn;


            $mapped[
                'usd_employer_transfer_in'
            ] =
                $employerTransferIn;


            $mapped[
                'usd_employee_late_interest'
            ] =
                $employeeLateInterest;


            $mapped[
                'usd_employer_late_interest'
            ] =
                $employerLateInterest;
        }


        /*
        |--------------------------------------------------------------------------
        | Keep Currency
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
                ??
                null
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
                ??
                null
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
                ??
                null
            )
            &&
            blank(
                $data[
                    'penad_member_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'penerp_member_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'staff_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'national_id'
                ]
                ??
                null
            )
        ) {
            $errors[] =
                'No PenAd/Pension reference number, PENERP member number, staff number or National ID was supplied.';
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
                ??
                null
            )
        ) {
            $errors[] =
                'Employer Number is missing from the contribution row.';

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
                    ??
                    null,

                $batch
                    ->employer
                    ->penad_employer_number
                    ??
                    null,

                $batch
                    ->employer
                    ->fundworx_employer_number
                    ??
                    null,
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
                ??
                null
            )
        ) {
            $warnings[] =
                'The contribution Due Date is missing.';

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
                    . (
                        $batch
                            ->contributionPeriod
                            ->period_label
                        ??
                        $batch
                            ->contributionPeriod
                            ->period_date
                    )
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
    |
    | Rate/calculation exceptions are WARNINGS.
    |
    | They do NOT prevent approval.
    |
    */

    private function validateFinancialValues(
        string $currency,
        array $data,
        bool $isNewMember,
        array &$warnings
    ): void {
        $prefix =
            strtolower(
                $currency
            );


        $basicPay =
            (float) (
                $data[
                    $prefix
                    . '_basic_pay'
                ]
                ??
                0
            );


        $employeeRate =
            $this->normalisePercentageRate(
                $data[
                    $prefix
                    . '_employee_rate'
                ]
                ??
                0
            );


        $employerRate =
            $this->normalisePercentageRate(
                $data[
                    $prefix
                    . '_employer_rate'
                ]
                ??
                0
            );


        $employeeContribution =
            (float) (
                $data[
                    $prefix
                    . '_employee_contribution'
                ]
                ??
                0
            );


        $employerContribution =
            (float) (
                $data[
                    $prefix
                    . '_employer_contribution'
                ]
                ??
                0
            );


        $employeeAvc =
            (float) (
                $data[
                    $prefix
                    . '_employee_avc'
                ]
                ??
                0
            );


        $employerAvc =
            (float) (
                $data[
                    $prefix
                    . '_employer_avc'
                ]
                ??
                0
            );


        $employeeArrear =
            (float) (
                $data[
                    $prefix
                    . '_employee_arrear'
                ]
                ??
                0
            );


        $employerArrear =
            (float) (
                $data[
                    $prefix
                    . '_employer_arrear'
                ]
                ??
                0
            );


        /*
        |--------------------------------------------------------------------------
        | Negative Contributions
        |--------------------------------------------------------------------------
        */

        $financialValues = [
            $employeeContribution,
            $employerContribution,
            $employeeAvc,
            $employerAvc,
            $employeeArrear,
            $employerArrear,
        ];


        if (
            collect(
                $financialValues
            )->contains(
                fn ($amount) =>
                    (float)
                    $amount < 0
            )
        ) {
            $warnings[] =
                'CONTRIBUTION EXCEPTION: Negative '
                . $currency
                . ' contribution detected. '
                . 'This row requires review as a possible adjustment/correction.';
        }


        /*
        |--------------------------------------------------------------------------
        | Basic Pay
        |--------------------------------------------------------------------------
        */

        if ($basicPay <= 0) {
            if (
                $employeeContribution != 0
                ||
                $employerContribution != 0
            ) {
                $warnings[] =
                    'CONTRIBUTION EXCEPTION: '
                    . $currency
                    . ' contribution exists but pensionable Basic Pay is zero or negative.';
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Employer Rate = 17.30%
        |--------------------------------------------------------------------------
        */

        $requiredEmployerRate =
            17.30;


        if (
            abs(
                $employerRate
                -
                $requiredEmployerRate
            )
            >
            0.001
        ) {
            $warnings[] =
                'RATE EXCEPTION: '
                . $currency
                . ' Employer Rate is '
                . number_format(
                    $employerRate,
                    2
                )
                . '%. Expected employer rate is 17.30%.';
        }


        /*
        |--------------------------------------------------------------------------
        | System Employer Contribution
        |--------------------------------------------------------------------------
        */

        $expectedEmployerContribution =
            round(
                $basicPay
                *
                (
                    $requiredEmployerRate
                    /
                    100
                ),
                2
            );


        if (
            abs(
                $employerContribution
                -
                $expectedEmployerContribution
            )
            >
            0.01
        ) {
            /*
            |--------------------------------------------------------------------------
            | Variance
            |--------------------------------------------------------------------------
            |
            | System calculated - uploaded schedule
            |
            */

            $variance =
                $expectedEmployerContribution
                -
                $employerContribution;


            $warnings[] =
                'CONTRIBUTION EXCEPTION: '
                . $currency
                . ' Employer Contribution does not agree with 17.30% of Basic Pay. '
                . 'Basic Pay: '
                . number_format(
                    $basicPay,
                    2
                )
                . ', System calculated: '
                . number_format(
                    $expectedEmployerContribution,
                    2
                )
                . ', Schedule amount: '
                . number_format(
                    $employerContribution,
                    2
                )
                . ', Variance: '
                . number_format(
                    $variance,
                    2
                )
                . '.';
        }


        /*
        |--------------------------------------------------------------------------
        | Employee Rate
        |--------------------------------------------------------------------------
        */

        if ($isNewMember) {
            /*
            |--------------------------------------------------------------------------
            | New Member = exactly 6.00%
            |--------------------------------------------------------------------------
            */

            $calculationEmployeeRate =
                6.00;


            if (
                abs(
                    $employeeRate
                    -
                    6.00
                )
                >
                0.001
            ) {
                $warnings[] =
                    'RATE EXCEPTION: '
                    . $currency
                    . ' Employee Rate for a proposed new member is '
                    . number_format(
                        $employeeRate,
                        2
                    )
                    . '%. New members must contribute at 6.00%.';
            }

        } else {
            /*
            |--------------------------------------------------------------------------
            | Existing Member = 5.00% to 6.00%
            |--------------------------------------------------------------------------
            */

            if (
                $employeeRate < 5.00
                ||
                $employeeRate > 6.00
            ) {
                $warnings[] =
                    'RATE EXCEPTION: '
                    . $currency
                    . ' Employee Rate is '
                    . number_format(
                        $employeeRate,
                        2
                    )
                    . '%. Existing members are expected to have an employee contribution rate between 5.00% and 6.00%.';
            }


            /*
            |--------------------------------------------------------------------------
            | Existing Member Calculation Uses Uploaded Rate
            |--------------------------------------------------------------------------
            */

            $calculationEmployeeRate =
                $employeeRate;
        }


        /*
        |--------------------------------------------------------------------------
        | System Employee Contribution
        |--------------------------------------------------------------------------
        */

        $expectedEmployeeContribution =
            round(
                $basicPay
                *
                (
                    $calculationEmployeeRate
                    /
                    100
                ),
                2
            );


        if (
            abs(
                $employeeContribution
                -
                $expectedEmployeeContribution
            )
            >
            0.01
        ) {
            $variance =
                $expectedEmployeeContribution
                -
                $employeeContribution;


            $warnings[] =
                'CONTRIBUTION EXCEPTION: '
                . $currency
                . ' Employee Contribution does not agree with '
                . number_format(
                    $calculationEmployeeRate,
                    2
                )
                . '% of Basic Pay. '
                . 'Basic Pay: '
                . number_format(
                    $basicPay,
                    2
                )
                . ', System calculated: '
                . number_format(
                    $expectedEmployeeContribution,
                    2
                )
                . ', Schedule amount: '
                . number_format(
                    $employeeContribution,
                    2
                )
                . ', Variance: '
                . number_format(
                    $variance,
                    2
                )
                . '.';
        }


        /*
        |--------------------------------------------------------------------------
        | Zero Contributions
        |--------------------------------------------------------------------------
        */

        if (
            $employeeContribution == 0
            &&
            $employerContribution == 0
            &&
            $employeeAvc == 0
            &&
            $employerAvc == 0
            &&
            $employeeArrear == 0
            &&
            $employerArrear == 0
        ) {
            $warnings[] =
                'All '
                . $currency
                . ' contribution amounts are zero.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Percentage Rate
    |--------------------------------------------------------------------------
    |
    | Supports:
    |
    | 6       => 6%
    | 0.06    => 6%
    | 17.3    => 17.3%
    | 0.173   => 17.3%
    |
    */

    private function normalisePercentageRate(
        mixed $value
    ): float {
        $rate =
            (float) (
                $value
                ??
                0
            );


        if (
            $rate > 0
            &&
            $rate <= 1
        ) {
            return round(
                $rate
                *
                100,
                6
            );
        }


        return $rate;
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
                ??
                null
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
                ??
                null
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
                ??
                null
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


        /*
        |--------------------------------------------------------------------------
        | Date Of Birth
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'date_of_birth'
                ]
                ??
                null
            )
            &&
            filled(
                $member
                    ->date_of_birth
            )
        ) {
            try {
                $excelDob =
                    Carbon::parse(
                        $data[
                            'date_of_birth'
                        ]
                    )
                        ->toDateString();


                $memberDob =
                    Carbon::parse(
                        $member
                            ->date_of_birth
                    )
                        ->toDateString();


                if (
                    $excelDob
                    !==
                    $memberDob
                ) {
                    $warnings[] =
                        'The Date of Birth on the contribution schedule differs from the Date of Birth stored against the matched member.';
                }

            } catch (Throwable) {
                $warnings[] =
                    'The Date of Birth on the contribution schedule could not be compared with the existing member record.';
            }
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
                    ??
                    ''
                )
            );


        if ($staffNumber === '') {
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


            if ($staffNumberAlreadyExists) {
                $errors[] =
                    'Staff number '
                    . $staffNumber
                    . ' is already assigned to a current member under this employer.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Mandatory New Member Fields
        |--------------------------------------------------------------------------
        */

        $requiredNewMemberFields = [
            'date_joined_fund' =>
                'Date Joined Fund',

            'date_joined_employer' =>
                'Date Joined Employer',

            'date_of_birth' =>
                'Date of Birth',

            'gender' =>
                'Gender',

            'national_id' =>
                'National ID',

            'marital_status' =>
                'Marital Status',

            'cellphone_number' =>
                'Cell Phone Number',

            'email_address' =>
                'Email Address',

            'home_address' =>
                'Home Address',
        ];


        foreach (
            $requiredNewMemberFields
            as $field => $label
        ) {
            if (
                blank(
                    $data[
                        $field
                    ]
                    ??
                    null
                )
            ) {
                $errors[] =
                    $label
                    . ' is required for a proposed new member.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | New Member Age
        |--------------------------------------------------------------------------
        |
        | HARD RULE:
        |
        | Proposed new member aged 60 or above cannot be created through the
        | monthly contribution upload.
        |
        */

        if (
            filled(
                $data[
                    'date_of_birth'
                ]
                ??
                null
            )
        ) {
            try {
                $dateOfBirth =
                    Carbon::parse(
                        $data[
                            'date_of_birth'
                        ]
                    );


                $ageDate =
                    filled(
                        $data[
                            'due_date'
                        ]
                        ??
                        null
                    )
                        ? Carbon::parse(
                            $data[
                                'due_date'
                            ]
                        )
                        : Carbon::parse(
                            $batch
                                ->contributionPeriod
                                ->period_date
                        );


                /*
                |--------------------------------------------------------------------------
                | Future DOB
                |--------------------------------------------------------------------------
                */

                if (
                    $dateOfBirth->gt(
                        $ageDate
                    )
                ) {
                    $errors[] =
                        'Date of Birth for the proposed new member cannot be after the contribution period date.';

                } else {
                    $age =
                        $dateOfBirth
                            ->diffInYears(
                                $ageDate
                            );


                    if ($age >= 60) {
                        $errors[] =
                            'The proposed new member is '
                            . $age
                            . ' years old at '
                            . $ageDate->format('d M Y')
                            . '. A new member aged 60 years or above cannot contribute or be created through the monthly contribution upload.';
                    }
                }

            } catch (Throwable) {
                $errors[] =
                    'Date of Birth for the proposed new member could not be interpreted.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Date Joined Fund
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'date_joined_fund'
                ]
                ??
                null
            )
            &&
            filled(
                $data[
                    'date_of_birth'
                ]
                ??
                null
            )
        ) {
            try {
                $dateJoinedFund =
                    Carbon::parse(
                        $data[
                            'date_joined_fund'
                        ]
                    );


                $dateOfBirth =
                    Carbon::parse(
                        $data[
                            'date_of_birth'
                        ]
                    );


                if (
                    $dateJoinedFund->lt(
                        $dateOfBirth
                    )
                ) {
                    $errors[] =
                        'Date Joined Fund cannot be before Date of Birth.';
                }

            } catch (Throwable) {
                $errors[] =
                    'Date Joined Fund could not be interpreted.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Date Joined Employer
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'date_joined_employer'
                ]
                ??
                null
            )
            &&
            filled(
                $data[
                    'date_of_birth'
                ]
                ??
                null
            )
        ) {
            try {
                $dateJoinedEmployer =
                    Carbon::parse(
                        $data[
                            'date_joined_employer'
                        ]
                    );


                $dateOfBirth =
                    Carbon::parse(
                        $data[
                            'date_of_birth'
                        ]
                    );


                if (
                    $dateJoinedEmployer->lt(
                        $dateOfBirth
                    )
                ) {
                    $errors[] =
                        'Date Joined Employer cannot be before Date of Birth.';
                }

            } catch (Throwable) {
                $errors[] =
                    'Date Joined Employer could not be interpreted.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | National ID Duplicate
        |--------------------------------------------------------------------------
        */

        $normalizedNationalId =
            Member::normalizeNationalId(
                $data[
                    'national_id'
                ]
                ??
                null
            );


        if ($normalizedNationalId) {
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
                        ??
                        ''
                    )
                    . ' already belongs to an existing PENERP member.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Email Format
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'email_address'
                ]
                ??
                null
            )
            &&
            !filter_var(
                $data[
                    'email_address'
                ],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'The Email Address supplied for the proposed new member is invalid.';
        }


        /*
        |--------------------------------------------------------------------------
        | Generated Member Number
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data[
                    'penerp_member_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'penad_member_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'pension_reference_number'
                ]
                ??
                null
            )
        ) {
            $warnings[] =
                'No existing PENERP/PenAd member number was supplied. '
                . 'A new member number will be allocated automatically when this batch is posted.';
        }


        $warnings[] =
            'No existing member matched this row. '
            . 'It has been classified as a proposed new member and has not yet been permanently created.';
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
                                ??
                                ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data[
                                    'penerp_member_number'
                                ]
                                ??
                                ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data[
                                    'staff_number'
                                ]
                                ??
                                ''
                            )
                        )
                    ),

                    strtoupper(
                        Member::normalizeNationalId(
                            $data[
                                'national_id'
                            ]
                            ??
                            null
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
    |
    | These are the actual amounts supplied by the employer schedule.
    |
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
                ??
                0
            );


        $totals[
            'usd_employee_contribution_total'
        ] +=
            (float) (
                $data[
                    'usd_employee_contribution'
                ]
                ??
                0
            );


        $totals[
            'usd_employer_contribution_total'
        ] +=
            (float) (
                $data[
                    'usd_employer_contribution'
                ]
                ??
                0
            );


        $totals[
            'usd_employee_avc_total'
        ] +=
            (float) (
                $data[
                    'usd_employee_avc'
                ]
                ??
                0
            );


        $totals[
            'usd_employer_avc_total'
        ] +=
            (float) (
                $data[
                    'usd_employer_avc'
                ]
                ??
                0
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
                ??
                0
            );


        $totals[
            'zwg_employee_contribution_total'
        ] +=
            (float) (
                $data[
                    'zwg_employee_contribution'
                ]
                ??
                0
            );


        $totals[
            'zwg_employer_contribution_total'
        ] +=
            (float) (
                $data[
                    'zwg_employer_contribution'
                ]
                ??
                0
            );


        $totals[
            'zwg_employee_avc_total'
        ] +=
            (float) (
                $data[
                    'zwg_employee_avc'
                ]
                ??
                0
            );


        $totals[
            'zwg_employer_avc_total'
        ] +=
            (float) (
                $data[
                    'zwg_employer_avc'
                ]
                ??
                0
            );
    }
}