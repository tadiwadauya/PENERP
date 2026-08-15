<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MemberStatusHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ContributionImportValidationService
{
    /*
    |--------------------------------------------------------------------------
    | Required Excel Columns
    |--------------------------------------------------------------------------
    */

    private array $requiredHeaders = [
        'Employer Number',
        'Scheme Code',
        'Due Date',

        'Surname',
        'First Name',

        'Date of Birth',
        'Gender',

        'National Registration Number',

        'Date Joined Fund',
        'Date Joined Employer',

        'Employee Code or Works number',
        'Pension Reference Number',

        'Payment Flag',

        'USD Basic Pay',
        'ZWG Basic Pay',

        'USD Employer Rate',
        'ZWG Employer Rate',

        'USD Employer Contribution',
        'ZWG Employer Contribution',

        'USD Employee Rate',
        'ZWG Employee Rate',

        'USD Employee Contribution',
        'ZWG Employee Contribution',
    ];


    public function process(
        ContributionImportBatch $batch
    ): void {
        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        $batch->update([
            'status' =>
                'processing',

            'progress_percentage' =>
                0,

            'processing_started_at' =>
                now(),

            'failure_reason' =>
                null,
        ]);


        try {

            /*
            |--------------------------------------------------------------------------
            | Locate Excel File
            |--------------------------------------------------------------------------
            */

            $fullPath =
                storage_path(
                    'app/'
                    . $batch->file_path
                );


            if (
                !file_exists(
                    $fullPath
                )
            ) {
                throw new RuntimeException(
                    'The uploaded contribution file could not be found.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Open Excel
            |--------------------------------------------------------------------------
            */

            $spreadsheet =
                IOFactory::load(
                    $fullPath
                );


            $sheet =
                $spreadsheet
                    ->getActiveSheet();


            $highestRow =
                $sheet
                    ->getHighestDataRow();


            $highestColumn =
                $sheet
                    ->getHighestDataColumn();


            /*
            |--------------------------------------------------------------------------
            | Read Header
            |--------------------------------------------------------------------------
            */

            $headerValues =
                $sheet->rangeToArray(
                    'A1:'
                    . $highestColumn
                    . '1',
                    null,
                    true,
                    false
                )[0];


            $headers = [];


            foreach (
                $headerValues
                as $index => $heading
            ) {
                $heading =
                    trim(
                        (string) $heading
                    );


                if (
                    $heading !== ''
                ) {
                    $headers[
                        $index
                    ] =
                        $heading;
                }
            }


            $this->validateHeaders(
                $headers
            );


            /*
            |--------------------------------------------------------------------------
            | Clear Previous Validation Results
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
                0;

            $validRows =
                0;

            $warningRows =
                0;

            $errorRows =
                0;

            $existingMembers =
                0;

            $newMembers =
                0;

            $scheduledMemberIds =
                [];


            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            $totals = [
                'usd_basic_pay_total' =>
                    0,

                'usd_employee_contribution_total' =>
                    0,

                'usd_employer_contribution_total' =>
                    0,

                'usd_employee_avc_total' =>
                    0,

                'usd_employer_avc_total' =>
                    0,

                'zwg_basic_pay_total' =>
                    0,

                'zwg_employee_contribution_total' =>
                    0,

                'zwg_employer_contribution_total' =>
                    0,

                'zwg_employee_avc_total' =>
                    0,

                'zwg_employer_avc_total' =>
                    0,
            ];


            /*
            |--------------------------------------------------------------------------
            | Process Excel Rows
            |--------------------------------------------------------------------------
            */

            for (
                $excelRow = 2;
                $excelRow <= $highestRow;
                $excelRow++
            ) {

                $values =
                    $sheet->rangeToArray(
                        'A'
                        . $excelRow
                        . ':'
                        . $highestColumn
                        . $excelRow,
                        null,
                        true,
                        false
                    )[0];


                if (
                    $this->isEmptyRow(
                        $values
                    )
                ) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | Ignore Total Rows
                |--------------------------------------------------------------------------
                */

                if (
                    strtoupper(
                        trim(
                            (string) (
                                $values[0]
                                ?? ''
                            )
                        )
                    )
                    ===
                    'TOTAL'
                ) {
                    continue;
                }


                $totalRows++;


                /*
                |--------------------------------------------------------------------------
                | Raw Excel Row
                |--------------------------------------------------------------------------
                */

                $rawData = [];


                foreach (
                    $headers
                    as $index => $heading
                ) {
                    $rawData[
                        $heading
                    ] =
                        $values[
                            $index
                        ]
                        ?? null;
                }


                $data =
                    $this->normalizeRow(
                        $rawData
                    );


                $errors =
                    [];

                $warnings =
                    [];


                /*
                |--------------------------------------------------------------------------
                | Employer Check
                |--------------------------------------------------------------------------
                */

                $this->validateEmployer(
                    $batch,
                    $data,
                    $errors
                );


                /*
                |--------------------------------------------------------------------------
                | Mandatory Member Information
                |--------------------------------------------------------------------------
                */

                if (
                    blank(
                        $data['surname']
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
                | Find Existing Member
                |--------------------------------------------------------------------------
                */

                $match =
                    $this->matchMember(
                        $batch->employer,
                        $data
                    );


                if (
                    $match['conflict']
                ) {
                    $errors[] =
                        $match['message'];
                }


                $member =
                    $match['member'];


                $matchType =
                    $match['match_type'];


                $isNewMember =
                    false;


                /*
                |--------------------------------------------------------------------------
                | Existing Member
                |--------------------------------------------------------------------------
                */

                if ($member) {

                    $existingMembers++;


                    $scheduledMemberIds[] =
                        $member->id;


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
                                'Member appeared on the monthly contribution schedule.',

                            'import_batch_id' =>
                                $batch->id,
                        ]
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | Employer Relationship Warning
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $member
                            ->currentEmployment
                        &&
                        $member
                            ->currentEmployment
                            ->employer_id
                        !=
                        $batch
                            ->employer_id
                    ) {
                        $warnings[] =
                            'The member currently belongs to another employer in PENERP.';
                    }

                } elseif (
                    !$match['conflict']
                    &&
                    empty($errors)
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | New Member
                    |--------------------------------------------------------------------------
                    */

                    $isNewMember =
                        true;


                    $this->validateNewMember(
                        $batch->employer,
                        $data,
                        $errors
                    );


                    if (
                        empty(
                            $errors
                        )
                    ) {

                        $member =
                            $this->createNewMember(
                                $batch,
                                $data
                            );


                        $newMembers++;


                        $scheduledMemberIds[] =
                            $member->id;


                        $matchType =
                            'new_member';


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
                                    'new_member',

                                'reason' =>
                                    'New member identified from the monthly contribution schedule.',

                                'import_batch_id' =>
                                    $batch->id,
                            ]
                        );
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Contribution Checks
                |--------------------------------------------------------------------------
                */

                $this->validateContributionAmounts(
                    $data,
                    $warnings
                );


                /*
                |--------------------------------------------------------------------------
                | Financial Totals
                |--------------------------------------------------------------------------
                */

                $totals[
                    'usd_basic_pay_total'
                ] +=
                    $data[
                        'usd_basic_pay'
                    ];


                $totals[
                    'usd_employee_contribution_total'
                ] +=
                    $data[
                        'usd_employee_contribution'
                    ];


                $totals[
                    'usd_employer_contribution_total'
                ] +=
                    $data[
                        'usd_employer_contribution'
                    ];


                $totals[
                    'usd_employee_avc_total'
                ] +=
                    $data[
                        'usd_employee_avc'
                    ];


                $totals[
                    'usd_employer_avc_total'
                ] +=
                    $data[
                        'usd_employer_avc'
                    ];


                $totals[
                    'zwg_basic_pay_total'
                ] +=
                    $data[
                        'zwg_basic_pay'
                    ];


                $totals[
                    'zwg_employee_contribution_total'
                ] +=
                    $data[
                        'zwg_employee_contribution'
                    ];


                $totals[
                    'zwg_employer_contribution_total'
                ] +=
                    $data[
                        'zwg_employer_contribution'
                    ];


                $totals[
                    'zwg_employee_avc_total'
                ] +=
                    $data[
                        'zwg_employee_avc'
                    ];


                $totals[
                    'zwg_employer_avc_total'
                ] +=
                    $data[
                        'zwg_employer_avc'
                    ];


                /*
                |--------------------------------------------------------------------------
                | Validation Status
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
                | Store Staging Row
                |--------------------------------------------------------------------------
                */

                ContributionImportRow::create([
                    'import_batch_id' =>
                        $batch->id,

                    'row_number' =>
                        $excelRow,

                    'raw_data' =>
                        $rawData,

                    'normalized_data' =>
                        $data,

                    'matched_member_id' =>
                        $isNewMember
                            ? null
                            : $member?->id,

                    'match_type' =>
                        $matchType,

                    'is_new_member' =>
                        $isNewMember,

                    'member_created' =>
                        $isNewMember
                        &&
                        $member !== null,

                    'created_member_id' =>
                        $isNewMember
                            ? $member?->id
                            : null,

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

                $batch->update([
                    'processed_rows' =>
                        $totalRows,

                    'progress_percentage' =>
                        min(
                            95,
                            (
                                $excelRow
                                /
                                max(
                                    1,
                                    $highestRow
                                )
                            )
                            * 100
                        ),
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | Nil Contributors
            |--------------------------------------------------------------------------
            */

            $nilContributors =
                $this->identifyNilContributors(
                    $batch,
                    array_unique(
                        $scheduledMemberIds
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Batch Completed
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
                    $existingMembers,

                'new_member_rows' =>
                    $newMembers,

                'nil_contributor_rows' =>
                    $nilContributors,

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
                        ),

                    'existing_members' =>
                        $existingMembers,

                    'new_members' =>
                        $newMembers,

                    'nil_contributors' =>
                        $nilContributors,

                    'updated_by' =>
                        $batch
                            ->uploaded_by,
                ]);

        } catch (Throwable $e) {

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
    | Validate Headers
    |--------------------------------------------------------------------------
    */

    private function validateHeaders(
        array $headers
    ): void {
        $existing =
            array_values(
                $headers
            );


        $missing =
            [];


        foreach (
            $this->requiredHeaders
            as $header
        ) {
            if (
                !in_array(
                    $header,
                    $existing,
                    true
                )
            ) {
                $missing[] =
                    $header;
            }
        }


        if (
            !empty(
                $missing
            )
        ) {
            throw new RuntimeException(
                'Missing Excel column(s): '
                . implode(
                    ', ',
                    $missing
                )
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Validation
    |--------------------------------------------------------------------------
    */

    private function validateEmployer(
        ContributionImportBatch $batch,
        array $data,
        array &$errors
    ): void {
        $number =
            trim(
                (string) (
                    $data[
                        'employer_number'
                    ]
                    ?? ''
                )
            );


        if (
            $number === ''
        ) {
            $errors[] =
                'Employer number is missing.';

            return;
        }


        $matchesBatchEmployer =
            $batch
                ->employer
                ->employer_number
                ===
                $number
            ||
            $batch
                ->employer
                ->penad_employer_number
                ===
                $number
            ||
            $batch
                ->employer
                ->fundworx_employer_number
                ===
                $number;


        if (
            !$matchesBatchEmployer
        ) {
            $errors[] =
                'Employer number '
                . $number
                . ' does not match the selected employer.';
        }
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
    | 3. Staff number under selected employer
    | 4. National ID
    |
    */

    private function matchMember(
        Employer $employer,
        array $data
    ): array {
        $matches =
            [];


        $memberNumber =
            trim(
                (string) (
                    $data[
                        'pension_reference_number'
                    ]
                    ?? ''
                )
            );


        /*
        |--------------------------------------------------------------------------
        | PenAd Number
        |--------------------------------------------------------------------------
        */

        if (
            $memberNumber !== ''
        ) {
            $member =
                Member::query()
                    ->where(
                        'penad_member_number',
                        $memberNumber
                    )
                    ->first();


            if ($member) {
                $matches[
                    'penad_number'
                ] =
                    $member;
            }


            /*
            |--------------------------------------------------------------------------
            | PENERP Number
            |--------------------------------------------------------------------------
            */

            $member =
                Member::query()
                    ->where(
                        'member_number',
                        $memberNumber
                    )
                    ->first();


            if ($member) {
                $matches[
                    'penerp_number'
                ] =
                    $member;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Staff Number Within Employer
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
            $staffNumber !== ''
        ) {
            $employment =
                MemberEmployment::query()
                    ->with(
                        'member'
                    )
                    ->where(
                        'employer_id',
                        $employer->id
                    )
                    ->where(
                        'staff_number',
                        $staffNumber
                    )
                    ->where(
                        'is_current',
                        true
                    )
                    ->first();


            if (
                $employment
                &&
                $employment->member
            ) {
                $matches[
                    'staff_number'
                ] =
                    $employment
                        ->member;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $nationalId =
            Member::normalizeNationalId(
                $data[
                    'national_id'
                ]
                ?? null
            );


        if ($nationalId) {
            $member =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $nationalId
                    )
                    ->first();


            if ($member) {
                $matches[
                    'national_id'
                ] =
                    $member;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | No Existing Member
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $matches
            )
        ) {
            return [
                'member' =>
                    null,

                'match_type' =>
                    null,

                'conflict' =>
                    false,

                'message' =>
                    null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Check Conflicting Identifiers
        |--------------------------------------------------------------------------
        */

        $memberIds =
            collect(
                $matches
            )
                ->pluck('id')
                ->unique()
                ->values();


        if (
            $memberIds->count()
            > 1
        ) {
            return [
                'member' =>
                    null,

                'match_type' =>
                    'conflict',

                'conflict' =>
                    true,

                'message' =>
                    'The member number, staff number and National ID identify different existing members.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Return Highest Priority Match
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'penad_number',
                'penerp_number',
                'staff_number',
                'national_id',
            ]
            as $type
        ) {
            if (
                isset(
                    $matches[$type]
                )
            ) {
                return [
                    'member' =>
                        $matches[$type],

                    'match_type' =>
                        $type,

                    'conflict' =>
                        false,

                    'message' =>
                        null,
                ];
            }
        }


        return [
            'member' =>
                null,

            'match_type' =>
                null,

            'conflict' =>
                false,

            'message' =>
                null,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | New Member Validation
    |--------------------------------------------------------------------------
    */

    private function validateNewMember(
        Employer $employer,
        array $data,
        array &$errors
    ): void {
        if (
            blank(
                $data[
                    'staff_number'
                ]
            )
        ) {
            $errors[] =
                'A staff number is required for a new member.';
        }


        if (
            blank(
                $data[
                    'national_id'
                ]
            )
        ) {
            $errors[] =
                'A National ID is required for a new member.';
        }


        /*
        |--------------------------------------------------------------------------
        | Staff Number Is Unique Only Within Employer
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'staff_number'
                ]
            )
        ) {
            $duplicate =
                MemberEmployment::query()
                    ->where(
                        'employer_id',
                        $employer->id
                    )
                    ->where(
                        'staff_number',
                        trim(
                            $data[
                                'staff_number'
                            ]
                        )
                    )
                    ->where(
                        'is_current',
                        true
                    )
                    ->exists();


            if ($duplicate) {
                $errors[] =
                    'Staff number '
                    . $data[
                        'staff_number'
                    ]
                    . ' is already assigned to another current member under this employer.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | National ID Global Uniqueness
        |--------------------------------------------------------------------------
        */

        $normalizedId =
            Member::normalizeNationalId(
                $data[
                    'national_id'
                ]
                ?? null
            );


        if ($normalizedId) {
            $duplicate =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $normalizedId
                    )
                    ->exists();


            if ($duplicate) {
                $errors[] =
                    'National ID already belongs to another PENERP member.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Create New Member
    |--------------------------------------------------------------------------
    */

    private function createNewMember(
        ContributionImportBatch $batch,
        array $data
    ): Member {
        return DB::transaction(
            function () use (
                $batch,
                $data
            ): Member {

                /*
                |--------------------------------------------------------------------------
                | Generate PENERP Number
                |--------------------------------------------------------------------------
                */

                $memberNumber =
                    $this
                        ->generateMemberNumber();


                $normalizedId =
                    Member::normalizeNationalId(
                        $data[
                            'national_id'
                        ]
                        ?? null
                    );


                /*
                |--------------------------------------------------------------------------
                | Create Member
                |--------------------------------------------------------------------------
                |
                | NEW MEMBER RULE:
                |
                | PENERP Number = PenAd Number
                |
                */

                $member =
                    Member::create([
                        'member_number' =>
                            $memberNumber,

                        'penad_member_number' =>
                            $memberNumber,

                        'fundworx_member_number' =>
                            null,

                        'surname' =>
                            $data[
                                'surname'
                            ],

                        'first_names' =>
                            $data[
                                'first_names'
                            ],

                        'other_names' =>
                            $data[
                                'other_names'
                            ]
                            ?? null,

                        'national_id' =>
                            $data[
                                'national_id'
                            ]
                            ?? null,

                        'national_id_normalized' =>
                            $normalizedId,

                        'date_of_birth' =>
                            $data[
                                'date_of_birth'
                            ]
                            ?? null,

                        'gender' =>
                            $data[
                                'gender'
                            ]
                            ?? null,

                        'marital_status' =>
                            $data[
                                'marital_status'
                            ]
                            ?? null,

                        'occupation' =>
                            $data[
                                'occupation'
                            ]
                            ?? null,

                        'date_joined_fund' =>
                            $data[
                                'date_joined_fund'
                            ]
                            ??
                            $batch
                                ->contributionPeriod
                                ->period_date
                                ->toDateString(),

                        'membership_status' =>
                            'active',

                        'is_active' =>
                            true,

                        'created_by' =>
                            $batch
                                ->uploaded_by,

                        'updated_by' =>
                            $batch
                                ->uploaded_by,
                    ]);


                /*
                |--------------------------------------------------------------------------
                | Employment
                |--------------------------------------------------------------------------
                */

                MemberEmployment::create([
                    'member_id' =>
                        $member->id,

                    'employer_id' =>
                        $batch
                            ->employer_id,

                    'staff_number' =>
                        $data[
                            'staff_number'
                        ]
                        ?? null,

                    'vote_number' =>
                        null,

                    'branch' =>
                        $data[
                            'branch'
                        ]
                        ?? null,

                    'department' =>
                        $data[
                            'department'
                        ]
                        ?? null,

                    'date_joined_employer' =>
                        $data[
                            'date_joined_employer'
                        ]
                        ?? null,

                    'effective_from' =>
                        $data[
                            'date_joined_employer'
                        ]
                        ??
                        $data[
                            'date_joined_fund'
                        ]
                        ??
                        $batch
                            ->contributionPeriod
                            ->period_date
                            ->toDateString(),

                    'effective_to' =>
                        null,

                    'employment_status' =>
                        'active',

                    'is_current' =>
                        true,

                    'created_by' =>
                        $batch
                            ->uploaded_by,

                    'updated_by' =>
                        $batch
                            ->uploaded_by,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Status History
                |--------------------------------------------------------------------------
                */

                MemberStatusHistory::create([
                    'member_id' =>
                        $member->id,

                    'old_status' =>
                        null,

                    'new_status' =>
                        'active',

                    'effective_date' =>
                        $data[
                            'date_joined_fund'
                        ]
                        ??
                        $batch
                            ->contributionPeriod
                            ->period_date
                            ->toDateString(),

                    'movement_type' =>
                        'NEW_MEMBER',

                    'reason' =>
                        'New member identified from monthly contribution schedule.',

                    'source' =>
                        'contribution_upload',

                    'changed_by' =>
                        $batch
                            ->uploaded_by,
                ]);


                return $member;
            }
        );
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
        $query =
            Member::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'currentEmployment',
                    function ($query) use (
                        $batch
                    ): void {

                        $query
                            ->where(
                                'employer_id',
                                $batch
                                    ->employer_id
                            )
                            ->where(
                                'is_current',
                                true
                            );
                    }
                );


        if (
            !empty(
                $scheduledMemberIds
            )
        ) {
            $query->whereNotIn(
                'id',
                $scheduledMemberIds
            );
        }


        $members =
            $query->get();


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
                        $member->id,
                ],
                [
                    'employer_id' =>
                        $batch
                            ->employer_id,

                    'contribution_status' =>
                        'nil_contributor',

                    'reason' =>
                        'Existing member did not appear on the monthly contribution schedule.',

                    'import_batch_id' =>
                        $batch->id,
                ]
            );
        }


        return $members->count();
    }


    /*
    |--------------------------------------------------------------------------
    | Contribution Validation
    |--------------------------------------------------------------------------
    */

    private function validateContributionAmounts(
        array $data,
        array &$warnings
    ): void {
        $values = [
            $data[
                'usd_employee_contribution'
            ],

            $data[
                'usd_employer_contribution'
            ],

            $data[
                'usd_employee_avc'
            ],

            $data[
                'usd_employer_avc'
            ],

            $data[
                'zwg_employee_contribution'
            ],

            $data[
                'zwg_employer_contribution'
            ],

            $data[
                'zwg_employee_avc'
            ],

            $data[
                'zwg_employer_avc'
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Negative Contributions Are Legitimate Adjustments
        |--------------------------------------------------------------------------
        */

        if (
            collect(
                $values
            )
                ->contains(
                    fn ($amount) =>
                        (float) $amount
                        <
                        0
                )
        ) {
            $warnings[] =
                'Negative contribution detected. This will be treated as a contribution adjustment.';
        }
    }


    private function generateMemberNumber(): string
    {
        $lastId =
            (int) (
                Member::withTrashed()
                    ->max('id')
                ??
                0
            );


        return 'MEM'
            . str_pad(
                (string) (
                    $lastId
                    +
                    1
                ),
                8,
                '0',
                STR_PAD_LEFT
            );
    }


    private function normalizeRow(
        array $data
    ): array {
        return [
            'employer_number' =>
                $this->clean(
                    $data[
                        'Employer Number'
                    ]
                    ?? null
                ),

            'scheme_code' =>
                $this->clean(
                    $data[
                        'Scheme Code'
                    ]
                    ?? null
                ),

            'due_date' =>
                $this->date(
                    $data[
                        'Due Date'
                    ]
                    ?? null
                ),

            'surname' =>
                $this->clean(
                    $data[
                        'Surname'
                    ]
                    ?? null
                ),

            'first_names' =>
                $this->clean(
                    $data[
                        'First Name'
                    ]
                    ?? null
                ),

            'other_names' =>
                $this->clean(
                    $data[
                        'Other Names'
                    ]
                    ?? null
                ),

            'branch' =>
                $this->clean(
                    $data[
                        'Branch'
                    ]
                    ?? null
                ),

            'department' =>
                $this->clean(
                    $data[
                        'Department'
                    ]
                    ?? null
                ),

            'date_of_birth' =>
                $this->date(
                    $data[
                        'Date of Birth'
                    ]
                    ?? null
                ),

            'gender' =>
                $this->clean(
                    $data[
                        'Gender'
                    ]
                    ?? null
                ),

            'national_id' =>
                $this->clean(
                    $data[
                        'National Registration Number'
                    ]
                    ?? null
                ),

            'marital_status' =>
                $this->clean(
                    $data[
                        'Marital Status'
                    ]
                    ?? null
                ),

            'date_joined_fund' =>
                $this->date(
                    $data[
                        'Date Joined Fund'
                    ]
                    ?? null
                ),

            'date_joined_employer' =>
                $this->date(
                    $data[
                        'Date Joined Employer'
                    ]
                    ?? null
                ),

            'staff_number' =>
                $this->clean(
                    $data[
                        'Employee Code or Works number'
                    ]
                    ?? null
                ),

            'pension_reference_number' =>
                $this->clean(
                    $data[
                        'Pension Reference Number'
                    ]
                    ?? null
                ),

            'occupation' =>
                $this->clean(
                    $data[
                        'Occupation'
                    ]
                    ?? null
                ),

            'payment_flag' =>
                $this->clean(
                    $data[
                        'Payment Flag'
                    ]
                    ?? null
                ),

            'usd_basic_pay' =>
                $this->number(
                    $data[
                        'USD Basic Pay'
                    ]
                    ?? null
                ),

            'zwg_basic_pay' =>
                $this->number(
                    $data[
                        'ZWG Basic Pay'
                    ]
                    ?? null
                ),

            'usd_employer_rate' =>
                $this->number(
                    $data[
                        'USD Employer Rate'
                    ]
                    ?? null
                ),

            'zwg_employer_rate' =>
                $this->number(
                    $data[
                        'ZWG Employer Rate'
                    ]
                    ?? null
                ),

            'usd_employer_contribution' =>
                $this->number(
                    $data[
                        'USD Employer Contribution'
                    ]
                    ?? null
                ),

            'zwg_employer_contribution' =>
                $this->number(
                    $data[
                        'ZWG Employer Contribution'
                    ]
                    ?? null
                ),

            'usd_employee_rate' =>
                $this->number(
                    $data[
                        'USD Employee Rate'
                    ]
                    ?? null
                ),

            'zwg_employee_rate' =>
                $this->number(
                    $data[
                        'ZWG Employee Rate'
                    ]
                    ?? null
                ),

            'usd_employee_contribution' =>
                $this->number(
                    $data[
                        'USD Employee Contribution'
                    ]
                    ?? null
                ),

            'zwg_employee_contribution' =>
                $this->number(
                    $data[
                        'ZWG Employee Contribution'
                    ]
                    ?? null
                ),

            'usd_employer_avc' =>
                $this->number(
                    $data[
                        'USD Employer Voluntary Contribution'
                    ]
                    ?? null
                ),

            'zwg_employer_avc' =>
                $this->number(
                    $data[
                        'ZWG Employer Voluntary Contribution'
                    ]
                    ?? null
                ),

            'usd_employee_avc' =>
                $this->number(
                    $data[
                        'USD Employee Voluntary Contribution'
                    ]
                    ?? null
                ),

            'zwg_employee_avc' =>
                $this->number(
                    $data[
                        'ZWG Employee Voluntary Contribution'
                    ]
                    ?? null
                ),

            'usd_employee_arrear' =>
                $this->number(
                    $data[
                        'USD EMPLOYEE ARREAR CONTRIBUTION'
                    ]
                    ?? null
                ),

            'usd_employer_arrear' =>
                $this->number(
                    $data[
                        'USD EMPLOYER ARREAR CONTRIBUTION'
                    ]
                    ?? null
                ),

            'zwg_employee_arrear' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYEE ARREAR CONTRIBUTION'
                    ]
                    ?? null
                ),

            'zwg_employer_arrear' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYER ARREAR CONTRIBUTION'
                    ]
                    ?? null
                ),

            'usd_employee_transfer_in' =>
                $this->number(
                    $data[
                        'USD EMPLOYEE TRANSFER IN'
                    ]
                    ??
                    $data[
                        'USD EMPLOYEe TRANSFER IN'
                    ]
                    ??
                    null
                ),

            'usd_employer_transfer_in' =>
                $this->number(
                    $data[
                        'USD EMPLOYER TRANSFER IN'
                    ]
                    ?? null
                ),

            'zwg_employee_transfer_in' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYEE TRANSFER IN'
                    ]
                    ?? null
                ),

            'zwg_employer_transfer_in' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYER TRANSFER IN'
                    ]
                    ?? null
                ),

            'usd_employee_late_interest' =>
                $this->number(
                    $data[
                        'USD EMPLOYEE LATE PAYMENT INTEREST'
                    ]
                    ?? null
                ),

            'usd_employer_late_interest' =>
                $this->number(
                    $data[
                        'USD EMPLOYER LATE PAYMENT INTEREST'
                    ]
                    ?? null
                ),

            'zwg_employee_late_interest' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYEE LATE PAYMENT INTEREST'
                    ]
                    ?? null
                ),

            'zwg_employer_late_interest' =>
                $this->number(
                    $data[
                        'ZWG EMPLOYER LATE PAYMENT INTEREST'
                    ]
                    ?? null
                ),

            'comments' =>
                $this->clean(
                    $data[
                        'Comments'
                    ]
                    ?? null
                ),
        ];
    }


    private function clean(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }


        return trim(
            (string) $value
        );
    }


    private function number(
        mixed $value
    ): float {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return 0;
        }


        if (
            is_numeric(
                $value
            )
        ) {
            return (float) $value;
        }


        return (float) str_replace(
            [
                ',',
                ' ',
            ],
            '',
            (string) $value
        );
    }


    private function date(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }


        try {

            if (
                is_numeric(
                    $value
                )
            ) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->toDateString();
            }


            return Carbon::parse(
                $value
            )->toDateString();

        } catch (Throwable) {

            return null;
        }
    }


    private function isEmptyRow(
        array $values
    ): bool {
        foreach (
            $values
            as $value
        ) {
            if (
                $value !== null
                &&
                trim(
                    (string) $value
                )
                !==
                ''
            ) {
                return false;
            }
        }


        return true;
    }
}