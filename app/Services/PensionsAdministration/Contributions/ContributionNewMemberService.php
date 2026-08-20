<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Services\PensionsAdministration\Updates\MemberNumberGeneratorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class ContributionNewMemberService
{
    public function __construct(
        private readonly MemberNumberGeneratorService $memberNumberGenerator
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Create New Member From Contribution Schedule
    |--------------------------------------------------------------------------
    */

    public function create(
        ContributionImportRow $row
    ): Member {
        /*
        |--------------------------------------------------------------------------
        | Load Required Relationships
        |--------------------------------------------------------------------------
        */

        $row->load([
            'batch.employer',
            'batch.contributionPeriod',
        ]);


        $batch =
            $row->batch;


        /*
        |--------------------------------------------------------------------------
        | Validate Batch
        |--------------------------------------------------------------------------
        */

        if (!$batch) {
            throw new RuntimeException(
                'The contribution row does not belong to a valid contribution batch.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Employer
        |--------------------------------------------------------------------------
        */

        if (!$batch->employer) {
            throw new RuntimeException(
                'The contribution batch does not have a valid employer.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Contribution Period
        |--------------------------------------------------------------------------
        */

        if (!$batch->contributionPeriod) {
            throw new RuntimeException(
                'The contribution batch does not have a valid contribution period.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Must Be Proposed New Member
        |--------------------------------------------------------------------------
        */

        if (!$row->is_new_member) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ' is not classified as a proposed new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Permanent Creation
        |--------------------------------------------------------------------------
        */

        if (
            $row->member_created
            &&
            $row->created_member_id
        ) {
            $existingCreatedMember =
                Member::query()
                    ->find(
                        $row->created_member_id
                    );


            if ($existingCreatedMember) {
                return $existingCreatedMember;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Normalized Excel Data
        |--------------------------------------------------------------------------
        */

        $data =
            $row->normalized_data
            ??
            [];


        /*
        |--------------------------------------------------------------------------
        | Surname
        |--------------------------------------------------------------------------
        */

        $surname =
            $this->requiredString(
                $data,
                'surname',
                'Surname',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | First Names
        |--------------------------------------------------------------------------
        */

        $firstNames =
            $this->requiredString(
                $data,
                'first_names',
                'First Names',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        $staffNumber =
            $this->requiredString(
                $data,
                'staff_number',
                'Staff Number',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        |
        | National ID is mandatory for a new member.
        |
        */

        $nationalId =
            $this->requiredString(
                $data,
                'national_id',
                'National ID',
                $row
            );


        $normalizedNationalId =
            Member::normalizeNationalId(
                $nationalId
            );


        if (!$normalizedNationalId) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': National ID could not be normalized for the proposed new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Of Birth
        |--------------------------------------------------------------------------
        */

        $dateOfBirth =
            $this->requiredDate(
                $data,
                'date_of_birth',
                'Date of Birth',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Date Joined Fund
        |--------------------------------------------------------------------------
        */

        $dateJoinedFund =
            $this->requiredDate(
                $data,
                'date_joined_fund',
                'Date Joined Fund',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Date Joined Employer
        |--------------------------------------------------------------------------
        |
        | This is now explicitly required for new members.
        |
        */

        $dateJoinedEmployer =
            $this->requiredDate(
                $data,
                'date_joined_employer',
                'Date Joined Employer',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Gender
        |--------------------------------------------------------------------------
        */

        $gender =
            $this->requiredString(
                $data,
                'gender',
                'Gender',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Marital Status
        |--------------------------------------------------------------------------
        */

        $maritalStatus =
            $this->requiredString(
                $data,
                'marital_status',
                'Marital Status',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Cell Phone Number
        |--------------------------------------------------------------------------
        */

        $cellphoneNumber =
            $this->requiredString(
                $data,
                'cellphone_number',
                'Cell Phone Number',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Email Address
        |--------------------------------------------------------------------------
        */

        $emailAddress =
            $this->requiredString(
                $data,
                'email_address',
                'Email Address',
                $row
            );


        if (
            !filter_var(
                $emailAddress,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Email Address '
                . $emailAddress
                . ' is not valid.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Home Address
        |--------------------------------------------------------------------------
        */

        $homeAddress =
            $this->requiredString(
                $data,
                'home_address',
                'Home Address',
                $row
            );


        /*
        |--------------------------------------------------------------------------
        | Age Validation
        |--------------------------------------------------------------------------
        |
        | Business Rule:
        |
        | A genuinely new member aged 60 years or above cannot be admitted
        | through the monthly contribution process.
        |
        | Age is measured at the contribution due date where available.
        |
        */

        $ageReferenceDate =
            $this->resolveAgeReferenceDate(
                $data,
                $batch->due_date,
                $batch->contributionPeriod->period_date
            );


        $age =
            $dateOfBirth
                ->diffInYears(
                    $ageReferenceDate
                );


        if ($age >= 60) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': The proposed new member is '
                . $age
                . ' years old at '
                . $ageReferenceDate->format('d M Y')
                . '. A new member aged 60 years or above cannot be created or contribute through the monthly contribution upload.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Consistency
        |--------------------------------------------------------------------------
        */

        if (
            $dateJoinedFund->lt(
                $dateOfBirth
            )
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Date Joined Fund cannot be before Date of Birth.'
            );
        }


        if (
            $dateJoinedEmployer->lt(
                $dateOfBirth
            )
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Date Joined Employer cannot be before Date of Birth.'
            );
        }


        if (
            $dateJoinedFund->gt(
                $ageReferenceDate
            )
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Date Joined Fund cannot be after the contribution period.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Staff Number Duplicate Check
        |--------------------------------------------------------------------------
        |
        | Staff number is unique only within an employer.
        |
        */

        $staffExists =
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


        if ($staffExists) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Staff Number '
                . $staffNumber
                . ' is already assigned to a current member under '
                . $batch->employer->name
                . '.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | National ID Duplicate Check
        |--------------------------------------------------------------------------
        */

        $nationalIdExists =
            Member::query()
                ->where(
                    'national_id_normalized',
                    $normalizedNationalId
                )
                ->exists();


        if ($nationalIdExists) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': National ID '
                . $nationalId
                . ' already belongs to an existing member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Posting User
        |--------------------------------------------------------------------------
        |
        | Do NOT rely only on auth()->id().
        |
        | Contribution posting may execute through a queued job where no web
        | authentication session exists.
        |
        */

        $postingUserId =
            $batch->posting_user_id
            ??
            $batch->posted_by
            ??
            auth()->id();


        if (!$postingUserId) {
            throw new RuntimeException(
                'Unable to determine the user responsible for creating the new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Permanent Creation
        |--------------------------------------------------------------------------
        */

        return DB::transaction(
            function () use (
                $row,
                $batch,
                $data,
                $surname,
                $firstNames,
                $staffNumber,
                $nationalId,
                $normalizedNationalId,
                $dateOfBirth,
                $dateJoinedFund,
                $dateJoinedEmployer,
                $gender,
                $maritalStatus,
                $cellphoneNumber,
                $emailAddress,
                $homeAddress,
                $postingUserId
            ): Member {

                /*
                |--------------------------------------------------------------------------
                | Lock Row
                |--------------------------------------------------------------------------
                |
                | Protect against the same row being processed twice concurrently.
                |
                */

                $lockedRow =
                    ContributionImportRow::query()
                        ->whereKey(
                            $row->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();


                /*
                |--------------------------------------------------------------------------
                | Check Again After Lock
                |--------------------------------------------------------------------------
                */

                if (
                    $lockedRow->member_created
                    &&
                    $lockedRow->created_member_id
                ) {
                    $existingMember =
                        Member::query()
                            ->find(
                                $lockedRow->created_member_id
                            );


                    if ($existingMember) {
                        return $existingMember;
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Re-check Staff Number Inside Transaction
                |--------------------------------------------------------------------------
                */

                $staffExists =
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
                        ->lockForUpdate()
                        ->exists();


                if ($staffExists) {
                    throw new RuntimeException(
                        'Excel row '
                        . $row->row_number
                        . ': Staff Number '
                        . $staffNumber
                        . ' was assigned to another current member before posting completed.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Re-check National ID Inside Transaction
                |--------------------------------------------------------------------------
                */

                $nationalIdExists =
                    Member::query()
                        ->where(
                            'national_id_normalized',
                            $normalizedNationalId
                        )
                        ->lockForUpdate()
                        ->exists();


                if ($nationalIdExists) {
                    throw new RuntimeException(
                        'Excel row '
                        . $row->row_number
                        . ': National ID '
                        . $nationalId
                        . ' was assigned to another member before posting completed.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Allocate New Member Number
                |--------------------------------------------------------------------------
                */

                $memberNumber =
                    $this
                        ->memberNumberGenerator
                        ->next();


                /*
                |--------------------------------------------------------------------------
                | Create Member
                |--------------------------------------------------------------------------
                */

                $member =
                    new Member();


                $member->forceFill([

                    /*
                    |--------------------------------------------------------------------------
                    | Member Numbers
                    |--------------------------------------------------------------------------
                    |
                    | New PENERP member:
                    |
                    | PENERP number = PenAd number
                    |
                    */

                    'member_number' =>
                        $memberNumber,

                    'penad_member_number' =>
                        $memberNumber,

                    'fundworx_member_number' =>
                        $this->nullableString(
                            $data[
                                'fundworx_member_number'
                            ]
                            ??
                            null
                        ),


                    /*
                    |--------------------------------------------------------------------------
                    | Name
                    |--------------------------------------------------------------------------
                    */

                    'surname' =>
                        $surname,

                    'first_names' =>
                        $firstNames,

                    'other_names' =>
                        $this->nullableString(
                            $data[
                                'other_names'
                            ]
                            ??
                            null
                        ),


                    /*
                    |--------------------------------------------------------------------------
                    | Identity
                    |--------------------------------------------------------------------------
                    */

                    'national_id' =>
                        $nationalId,

                    'national_id_normalized' =>
                        $normalizedNationalId,

                    'gender' =>
                        $gender,

                    'date_of_birth' =>
                        $dateOfBirth->toDateString(),


                    /*
                    |--------------------------------------------------------------------------
                    | Contact / Personal Information
                    |--------------------------------------------------------------------------
                    */

                    'marital_status' =>
                        $maritalStatus,

                    'cellphone_number' =>
                        $cellphoneNumber,

                    'email_address' =>
                        strtolower(
                            $emailAddress
                        ),

                    'home_address' =>
                        $homeAddress,


                    /*
                    |--------------------------------------------------------------------------
                    | Fund Membership
                    |--------------------------------------------------------------------------
                    */

                    'date_joined_fund' =>
                        $dateJoinedFund
                            ->toDateString(),

                    'membership_status' =>
                        'active',

                    'is_active' =>
                        true,


                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    'created_by' =>
                        $postingUserId,

                    'updated_by' =>
                        $postingUserId,
                ]);


                $member->save();


                /*
                |--------------------------------------------------------------------------
                | Create Member Employment
                |--------------------------------------------------------------------------
                */

                $employment =
                    new MemberEmployment();


                $employment->forceFill([

                    /*
                    |--------------------------------------------------------------------------
                    | Relationships
                    |--------------------------------------------------------------------------
                    */

                    'member_id' =>
                        $member->id,

                    'employer_id' =>
                        $batch->employer_id,


                    /*
                    |--------------------------------------------------------------------------
                    | Employment Reference
                    |--------------------------------------------------------------------------
                    */

                    'staff_number' =>
                        $staffNumber,

                    'vote_number' =>
                        $this->nullableString(
                            $data[
                                'vote_number'
                            ]
                            ??
                            null
                        ),

                    'branch' =>
                        $this->nullableString(
                            $data[
                                'branch'
                            ]
                            ??
                            null
                        ),

                    'department' =>
                        $this->nullableString(
                            $data[
                                'department'
                            ]
                            ??
                            null
                        ),


                    /*
                    |--------------------------------------------------------------------------
                    | Employment Dates
                    |--------------------------------------------------------------------------
                    */

                    'date_joined_employer' =>
                        $dateJoinedEmployer
                            ->toDateString(),

                    'effective_from' =>
                        $dateJoinedEmployer
                            ->toDateString(),

                    'effective_to' =>
                        null,


                    /*
                    |--------------------------------------------------------------------------
                    | Employment Status
                    |--------------------------------------------------------------------------
                    */

                    'employment_status' =>
                        'active',

                    'is_current' =>
                        true,


                    /*
                    |--------------------------------------------------------------------------
                    | Audit
                    |--------------------------------------------------------------------------
                    */

                    'created_by' =>
                        $postingUserId,

                    'updated_by' =>
                        $postingUserId,
                ]);


                $employment->save();


                /*
                |--------------------------------------------------------------------------
                | Update Contribution Staging Row
                |--------------------------------------------------------------------------
                */

                $normalizedData =
                    $lockedRow->normalized_data
                    ??
                    [];


                /*
                |--------------------------------------------------------------------------
                | Store Generated Numbers
                |--------------------------------------------------------------------------
                */

                $normalizedData[
                    'penerp_member_number'
                ] =
                    $memberNumber;


                $normalizedData[
                    'penad_member_number'
                ] =
                    $memberNumber;


                $normalizedData[
                    'pension_reference_number'
                ] =
                    $memberNumber;


                /*
                |--------------------------------------------------------------------------
                | Preserve Permanent Member Details
                |--------------------------------------------------------------------------
                */

                $normalizedData[
                    'marital_status'
                ] =
                    $maritalStatus;


                $normalizedData[
                    'cellphone_number'
                ] =
                    $cellphoneNumber;


                $normalizedData[
                    'email_address'
                ] =
                    strtolower(
                        $emailAddress
                    );


                $normalizedData[
                    'home_address'
                ] =
                    $homeAddress;


                /*
                |--------------------------------------------------------------------------
                | Mark Member Created
                |--------------------------------------------------------------------------
                */

                $lockedRow->update([
                    'matched_member_id' =>
                        $member->id,

                    'created_member_id' =>
                        $member->id,

                    'member_created' =>
                        true,

                    'match_type' =>
                        'new_member_created',

                    'normalized_data' =>
                        $normalizedData,
                ]);


                /*
                |--------------------------------------------------------------------------
                | Refresh Original Row Object
                |--------------------------------------------------------------------------
                */

                $row->refresh();


                return $member;
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Required String
    |--------------------------------------------------------------------------
    */

    private function requiredString(
        array $data,
        string $field,
        string $label,
        ContributionImportRow $row
    ): string {
        $value =
            trim(
                (string) (
                    $data[
                        $field
                    ]
                    ??
                    ''
                )
            );


        if ($value === '') {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': '
                . $label
                . ' is required for a new member.'
            );
        }


        return $value;
    }


    /*
    |--------------------------------------------------------------------------
    | Required Date
    |--------------------------------------------------------------------------
    */

    private function requiredDate(
        array $data,
        string $field,
        string $label,
        ContributionImportRow $row
    ): Carbon {
        $value =
            $data[
                $field
            ]
            ??
            null;


        if (blank($value)) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': '
                . $label
                . ' is required for a new member.'
            );
        }


        try {
            return Carbon::parse(
                $value
            )->startOfDay();

        } catch (Throwable) {

            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': '
                . $label
                . ' contains an invalid date.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Nullable String
    |--------------------------------------------------------------------------
    */

    private function nullableString(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }


        $value =
            trim(
                (string)
                $value
            );


        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Age Reference Date
    |--------------------------------------------------------------------------
    */

    private function resolveAgeReferenceDate(
        array $data,
        mixed $batchDueDate,
        mixed $periodDate
    ): Carbon {
        /*
        |--------------------------------------------------------------------------
        | Excel Due Date
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data[
                    'due_date'
                ]
                ??
                null
            )
        ) {
            try {
                return Carbon::parse(
                    $data[
                        'due_date'
                    ]
                )->startOfDay();

            } catch (Throwable) {
                // Continue to batch due date.
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Batch Due Date
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $batchDueDate
            )
        ) {
            try {
                return Carbon::parse(
                    $batchDueDate
                )->startOfDay();

            } catch (Throwable) {
                // Continue to contribution period date.
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Contribution Period Date
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $periodDate
            )
        ) {
            try {
                return Carbon::parse(
                    $periodDate
                )->startOfDay();

            } catch (Throwable) {
                // Final failure below.
            }
        }


        throw new RuntimeException(
            'Unable to determine the contribution period date for new member age validation.'
        );
    }
}