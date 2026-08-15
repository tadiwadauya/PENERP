<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ContributionImportValidator
{
    public function __construct(
        private readonly ContributionExcelReader $excelReader,
        private readonly ContributionMemberMatcher $memberMatcher
    ) {
    }


    public function process(
        ContributionImportBatch $batch
    ): void {
        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        if (!$batch->employer) {
            throw new RuntimeException(
                'The contribution batch does not have a valid employer.'
            );
        }


        if (
            !$batch
                ->contributionPeriod
        ) {
            throw new RuntimeException(
                'The contribution batch does not have a valid contribution period.'
            );
        }


        $batch->update([
            'status' =>
                'processing',

            'progress_percentage' =>
                0,

            'processed_rows' =>
                0,

            'failure_reason' =>
                null,

            'processing_started_at' =>
                now(),

            'completed_at' =>
                null,
        ]);


        try {

            $fullPath =
                storage_path(
                    'app/'
                    . $batch
                        ->file_path
                );


            $excel =
                $this
                    ->excelReader
                    ->read(
                        $fullPath
                    );


            /*
            |--------------------------------------------------------------------------
            | Clear Previous Validation
            |--------------------------------------------------------------------------
            */

            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();


            ContributionPeriodMemberStatus::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $totalRows =
                count(
                    $excel[
                        'rows'
                    ]
                );


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
            | Scheduled Existing Members
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
            | Totals
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
            | Process Rows
            |--------------------------------------------------------------------------
            */

            foreach (
                $excel['rows']
                as $position => $excelRow
            ) {

                $data =
                    $excelRow[
                        'normalized_data'
                    ];


                $errors =
                    [];

                $warnings =
                    [];


                /*
                |--------------------------------------------------------------------------
                | Basic Validation
                |--------------------------------------------------------------------------
                */

                $this->validateRequiredMemberData(
                    $data,
                    $errors
                );


                $this->validateEmployerReference(
                    $batch,
                    $data,
                    $errors
                );


                $this->validatePeriod(
                    $batch,
                    $data,
                    $warnings
                );


                $this->validateFinancialValues(
                    $data,
                    $warnings
                );


                /*
                |--------------------------------------------------------------------------
                | Duplicate Schedule Row
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
                        ];
                }


                /*
                |--------------------------------------------------------------------------
                | Member Matching
                |--------------------------------------------------------------------------
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
                    ];


                $matchType =
                    $match[
                        'match_type'
                    ];


                $isNewMember =
                    false;


                if (
                    $match[
                        'conflict'
                    ]
                ) {
                    $errors[] =
                        $match[
                            'message'
                        ];
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


                    $this->validateExistingMemberEmployer(
                        $batch,
                        $member,
                        $warnings
                    );


                    $this->validateExistingMemberIdentity(
                        $member,
                        $data,
                        $warnings
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Monthly Status
                    |--------------------------------------------------------------------------
                    */

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
                                'contributed',

                            'reason' =>
                                'Member appears on the monthly expected contribution schedule.',

                            'import_batch_id' =>
                                $batch
                                    ->id,
                        ]
                    );

                } elseif (
                    !$match[
                        'conflict'
                    ]
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | New Member Candidate
                    |--------------------------------------------------------------------------
                    |
                    | IMPORTANT:
                    |
                    | The member is NOT created here.
                    |
                    */

                    $isNewMember =
                        true;


                    $matchType =
                        'new_member';


                    $this->validateNewMemberCandidate(
                        $data,
                        $errors,
                        $warnings
                    );


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
                | Row Status
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
                | Totals
                |--------------------------------------------------------------------------
                |
                | Totals reflect the Excel schedule itself.
                |
                */

                $this->addTotals(
                    $totals,
                    $data
                );


                /*
                |--------------------------------------------------------------------------
                | Save Staging Row
                |--------------------------------------------------------------------------
                */

                ContributionImportRow::create([
                    'import_batch_id' =>
                        $batch
                            ->id,

                    'row_number' =>
                        $excelRow[
                            'row_number'
                        ],

                    'raw_data' =>
                        $excelRow[
                            'raw_data'
                        ],

                    'normalized_data' =>
                        $data,

                    'matched_member_id' =>
                        $member
                            ?->id,

                    'match_type' =>
                        $matchType,

                    'is_new_member' =>
                        $isNewMember,

                    /*
                    |--------------------------------------------------------------------------
                    | No Member Creation During Validation
                    |--------------------------------------------------------------------------
                    */

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
                | Progress
                |--------------------------------------------------------------------------
                */

                $processed =
                    $position
                    +
                    1;


                $percentage =
                    (
                        $processed
                        /
                        max(
                            1,
                            $totalRows
                        )
                    )
                    *
                    100;


                $batch->update([
                    'processed_rows' =>
                        $processed,

                    'progress_percentage' =>
                        min(
                            95,
                            $percentage
                        ),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Nil Contributors
            |--------------------------------------------------------------------------
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
            | Batch Summary
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'status' =>
                    'awaiting_review',

                'progress_percentage' =>
                    100,

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

                ...$totals,

                'completed_at' =>
                    now(),
            ]);


            /*
            |--------------------------------------------------------------------------
            | Period Summary
            |--------------------------------------------------------------------------
            */

            $batch
                ->contributionPeriod
                ->update([
                    'status' =>
                        'awaiting_review',

                    'scheduled_members' =>
                        count(
                            array_unique(
                                $scheduledMemberIds
                            )
                        )
                        +
                        $newMemberRows,

                    'existing_members' =>
                        count(
                            array_unique(
                                $scheduledMemberIds
                            )
                        ),

                    'new_members' =>
                        $newMemberRows,

                    'nil_contributors' =>
                        $nilContributorCount,

                    'updated_by' =>
                        $batch
                            ->uploaded_by,
                ]);

        } catch (Throwable $e) {

            $batch->update([
                'status' =>
                    'failed',

                'failure_reason' =>
                    $e
                        ->getMessage(),

                'completed_at' =>
                    now(),
            ]);


            throw $e;
        }
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
            )
        ) {
            $errors[] =
                'First name is missing.';
        }


        /*
        |--------------------------------------------------------------------------
        | At Least One Useful Identifier
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data[
                    'pension_reference_number'
                ]
            )
            &&
            blank(
                $data[
                    'penerp_member_number'
                ]
            )
            &&
            blank(
                $data[
                    'staff_number'
                ]
            )
            &&
            blank(
                $data[
                    'national_id'
                ]
            )
        ) {
            $errors[] =
                'No member number, staff number or National ID was supplied.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Reference
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
            )
        ) {
            return;
        }


        $excelEmployer =
            strtoupper(
                trim(
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
                ->filter()
                ->map(
                    fn ($value) =>
                        strtoupper(
                            trim(
                                (string) $value
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
                'The employer number in the Excel row does not match the employer selected for this upload.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Period Validation
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
            )
        ) {
            return;
        }


        $rowDate =
            \Carbon\Carbon::parse(
                $data[
                    'due_date'
                ]
            );


        if (
            $rowDate->year
            !=
            $batch
                ->contributionPeriod
                ->period_year
            ||
            $rowDate->month
            !=
            $batch
                ->contributionPeriod
                ->period_month
        ) {
            $warnings[] =
                'The Excel due date is not in the selected contribution month.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Financial Validation
    |--------------------------------------------------------------------------
    */

    private function validateFinancialValues(
        array $data,
        array &$warnings
    ): void {
        $contributions = [
            $data[
                'usd_employee_contribution'
            ]
            ?? 0,

            $data[
                'usd_employer_contribution'
            ]
            ?? 0,

            $data[
                'usd_employee_avc'
            ]
            ?? 0,

            $data[
                'usd_employer_avc'
            ]
            ?? 0,

            $data[
                'zwg_employee_contribution'
            ]
            ?? 0,

            $data[
                'zwg_employer_contribution'
            ]
            ?? 0,

            $data[
                'zwg_employee_avc'
            ]
            ?? 0,

            $data[
                'zwg_employer_avc'
            ]
            ?? 0,
        ];


        /*
        |--------------------------------------------------------------------------
        | Negative Contributions
        |--------------------------------------------------------------------------
        */

        if (
            collect(
                $contributions
            )
                ->contains(
                    fn ($amount) =>
                        (float) $amount
                        <
                        0
                )
        ) {
            $warnings[] =
                'Negative expected contribution detected. This row requires review and will later be treated as an adjustment.';
        }


        /*
        |--------------------------------------------------------------------------
        | Zero Contribution
        |--------------------------------------------------------------------------
        */

        $allZero =
            collect(
                $contributions
            )
                ->every(
                    fn ($amount) =>
                        (float) $amount
                        ===
                        0.0
                );


        if ($allZero) {
            $warnings[] =
                'All contribution amounts are zero.';
        }


        /*
        |--------------------------------------------------------------------------
        | Salary But No Contribution
        |--------------------------------------------------------------------------
        */

        $totalSalary =
            (float) (
                $data[
                    'usd_basic_pay'
                ]
                ?? 0
            )
            +
            (float) (
                $data[
                    'zwg_basic_pay'
                ]
                ?? 0
            );


        if (
            $totalSalary > 0
            &&
            $allZero
        ) {
            $warnings[] =
                'Pensionable salary exists but normal contribution amounts are zero.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Member Employer
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


        if (
            $currentEmployment
            &&
            $currentEmployment
                ->employer_id
            !=
            $batch
                ->employer_id
        ) {
            $warnings[] =
                'The matched member currently belongs to another employer in PENERP.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Member Identity Difference
    |--------------------------------------------------------------------------
    */

    private function validateExistingMemberIdentity(
        Member $member,
        array $data,
        array &$warnings
    ): void {
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


        if (
            filled(
                $data[
                    'surname'
                ]
            )
            &&
            strtoupper(
                trim(
                    $data[
                        'surname'
                    ]
                )
            )
            !==
            strtoupper(
                trim(
                    $member
                        ->surname
                )
            )
        ) {
            $warnings[] =
                'The surname on the schedule differs from the surname stored against the matched member.';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | New Member Candidate
    |--------------------------------------------------------------------------
    */

    private function validateNewMemberCandidate(
        array $data,
        array &$errors,
        array &$warnings
    ): void {
        if (
            blank(
                $data[
                    'staff_number'
                ]
            )
        ) {
            $errors[] =
                'Staff number is required before this person can be created as a new member.';
        }


        if (
            blank(
                $data[
                    'national_id'
                ]
            )
        ) {
            $warnings[] =
                'National ID is missing for the proposed new member.';
        }


        if (
            blank(
                $data[
                    'date_of_birth'
                ]
            )
        ) {
            $warnings[] =
                'Date of birth is missing for the proposed new member.';
        }


        if (
            blank(
                $data[
                    'date_joined_fund'
                ]
            )
        ) {
            $warnings[] =
                'Date joined fund is missing for the proposed new member.';
        }


        $warnings[] =
            'No existing member matched this row. It has been classified as a proposed new member and has not yet been created.';
    }


    /*
    |--------------------------------------------------------------------------
    | Nil Contributors
    |--------------------------------------------------------------------------
    */

    private function identifyNilContributors(
        ContributionImportBatch $batch,
        array $scheduledMemberIds
    ): int {
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
                        'Active member did not appear on the contribution schedule for this month.',

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
                        ?? ''
                    ),
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Totals
    |--------------------------------------------------------------------------
    */

    private function addTotals(
        array &$totals,
        array $data
    ): void {
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