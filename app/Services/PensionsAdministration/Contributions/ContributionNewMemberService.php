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
        $row->load([
            'batch.employer',
            'batch.contributionPeriod',
        ]);

        $batch = $row->batch;

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

        if (!$batch->employer) {
            throw new RuntimeException(
                'The contribution batch does not have a valid employer.'
            );
        }

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
            ?? [];

        /*
        |--------------------------------------------------------------------------
        | Required Core Member Fields
        |--------------------------------------------------------------------------
        */

        $surname =
            $this->requiredString(
                $data,
                'surname',
                'Surname',
                $row
            );

        $firstNames =
            $this->requiredString(
                $data,
                'first_names',
                'First Names',
                $row
            );

        $staffNumber =
            $this->requiredString(
                $data,
                'staff_number',
                'Staff Number',
                $row
            );

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

        $dateOfBirth =
            $this->requiredDate(
                $data,
                'date_of_birth',
                'Date of Birth',
                $row
            );

        $dateJoinedFund =
            $this->requiredDate(
                $data,
                'date_joined_fund',
                'Date Joined Fund',
                $row
            );

        $dateJoinedEmployer =
            $this->requiredDate(
                $data,
                'date_joined_employer',
                'Date Joined Employer',
                $row
            );

        $gender =
            $this->requiredString(
                $data,
                'gender',
                'Gender',
                $row
            );

        $maritalStatus =
            $this->requiredString(
                $data,
                'marital_status',
                'Marital Status',
                $row
            );

        /*
        |--------------------------------------------------------------------------
        | Optional Contact Details
        |--------------------------------------------------------------------------
        |
        | Cell phone, email and home address are optional.
        |
        */

        $cellphoneNumber =
            $this->nullableString(
                $data['cellphone_number']
                ?? null
            );

        $emailAddress =
            $this->nullableString(
                $data['email_address']
                ?? null
            );

        $homeAddress =
            $this->nullableString(
                $data['home_address']
                ?? null
            );

        /*
        |--------------------------------------------------------------------------
        | Validate Email Only When Supplied
        |--------------------------------------------------------------------------
        */

        if (
            $emailAddress
            &&
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
        | Age Validation
        |--------------------------------------------------------------------------
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
| Queue jobs do not have an authenticated browser session.
| The posting user must therefore come from the batch/job.
|
*/

$postingUserId =
    $batch->posting_user_id
    ??
    $batch->posted_by
    ??
    $batch->approved_by
    ??
    $batch->uploaded_by;

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
                | Lock Contribution Row
                |--------------------------------------------------------------------------
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
                | Re-check Staff Number
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
                | Re-check National ID
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
                | Generate New Member Number
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
                    */

                    'member_number' =>
                        $memberNumber,

                    'penad_member_number' =>
                        $memberNumber,

                    'fundworx_member_number' =>
                        $this->nullableString(
                            $data['fundworx_member_number']
                            ?? null
                        ),

                    /*
                    |--------------------------------------------------------------------------
                    | Names
                    |--------------------------------------------------------------------------
                    */

                    'surname' =>
                        $surname,

                    'first_names' =>
                        $firstNames,

                    'other_names' =>
                        $this->nullableString(
                            $data['other_names']
                            ?? null
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
                        $dateOfBirth
                            ->toDateString(),

                    /*
                    |--------------------------------------------------------------------------
                    | Personal / Contact Details
                    |--------------------------------------------------------------------------
                    */

                    'marital_status' =>
                        $maritalStatus,

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT
                    |--------------------------------------------------------------------------
                    |
                    | These may be NULL.
                    |
                    */

                    'cellphone_number' =>
                        $cellphoneNumber,

                    'email_address' =>
                        $emailAddress
                            ? strtolower(
                                $emailAddress
                            )
                            : null,

                    'home_address' =>
                        $homeAddress,

                    /*
                    |--------------------------------------------------------------------------
                    | Membership
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
                | Create Employment
                |--------------------------------------------------------------------------
                */

                $employment =
                    new MemberEmployment();

                $employment->forceFill([
                    'member_id' =>
                        $member->id,

                    'employer_id' =>
                        $batch->employer_id,

                    'staff_number' =>
                        $staffNumber,

                    'vote_number' =>
                        $this->nullableString(
                            $data['vote_number']
                            ?? null
                        ),

                    'branch' =>
                        $this->nullableString(
                            $data['branch']
                            ?? null
                        ),

                    'department' =>
                        $this->nullableString(
                            $data['department']
                            ?? null
                        ),

                    'date_joined_employer' =>
                        $dateJoinedEmployer
                            ->toDateString(),

                    'effective_from' =>
                        $dateJoinedEmployer
                            ->toDateString(),

                    'effective_to' =>
                        null,

                    'employment_status' =>
                        'active',

                    'is_current' =>
                        true,

                    'created_by' =>
                        $postingUserId,

                    'updated_by' =>
                        $postingUserId,
                ]);

                $employment->save();

                /*
                |--------------------------------------------------------------------------
                | Update Staging Row
                |--------------------------------------------------------------------------
                */

                $normalizedData =
                    $lockedRow->normalized_data
                    ?? [];

                $normalizedData['penerp_member_number'] =
                    $memberNumber;

                $normalizedData['penad_member_number'] =
                    $memberNumber;

                $normalizedData['pension_reference_number'] =
                    $memberNumber;

                $normalizedData['marital_status'] =
                    $maritalStatus;

                /*
                |--------------------------------------------------------------------------
                | Optional Details
                |--------------------------------------------------------------------------
                */

                $normalizedData['cellphone_number'] =
                    $cellphoneNumber;

                $normalizedData['email_address'] =
                    $emailAddress
                        ? strtolower(
                            $emailAddress
                        )
                        : null;

                $normalizedData['home_address'] =
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
                    $data[$field]
                    ?? ''
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
            $data[$field]
            ?? null;

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
                (string) $value
            );

        return $value !== ''
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
                $data['due_date']
                ?? null
            )
        ) {
            try {
                return Carbon::parse(
                    $data['due_date']
                )->startOfDay();

            } catch (Throwable) {
                // Continue.
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Batch Due Date
        |--------------------------------------------------------------------------
        */

        if (filled($batchDueDate)) {
            try {
                return Carbon::parse(
                    $batchDueDate
                )->startOfDay();

            } catch (Throwable) {
                // Continue.
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Period Date
        |--------------------------------------------------------------------------
        */

        if (filled($periodDate)) {
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