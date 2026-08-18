<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Services\PensionsAdministration\Updates\MemberNumberGeneratorService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

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
        ]);


        $batch =
            $row->batch;


        /*
        |--------------------------------------------------------------------------
        | Batch
        |--------------------------------------------------------------------------
        */

        if (!$batch) {
            throw new RuntimeException(
                'The contribution row does not belong to a valid contribution batch.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        if (!$batch->employer) {
            throw new RuntimeException(
                'The contribution batch does not have a valid employer.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Must Be Proposed New Member
        |--------------------------------------------------------------------------
        */

        if (
            !$row->is_new_member
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ' is not classified as a proposed new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Normalised Excel Data
        |--------------------------------------------------------------------------
        */

        $data =
            $row->normalized_data
            ?? [];


        /*
        |--------------------------------------------------------------------------
        | Surname
        |--------------------------------------------------------------------------
        */

        $surname =
            trim(
                (string) (
                    $data[
                        'surname'
                    ]
                    ?? ''
                )
            );


        if (
            $surname ===
            ''
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Surname is required for a new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | First Names
        |--------------------------------------------------------------------------
        */

        $firstNames =
            trim(
                (string) (
                    $data[
                        'first_names'
                    ]
                    ?? ''
                )
            );


        if (
            $firstNames ===
            ''
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': First Names are required for a new member.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Joined Fund
        |--------------------------------------------------------------------------
        |
        | This is mandatory for a genuinely new member.
        |
        */

        $dateJoinedFund =
            $data[
                'date_joined_fund'
            ]
            ?? null;


        if (
            blank(
                $dateJoinedFund
            )
        ) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Date Joined Fund is required before the new member can be created.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Date Joined Employer
        |--------------------------------------------------------------------------
        |
        | If the contribution schedule does not provide Date Joined Employer,
        | use Date Joined Fund as the employment commencement date.
        |
        */

        $dateJoinedEmployer =
            $data[
                'date_joined_employer'
            ]
            ??
            $dateJoinedFund;


        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        |
        | Staff Number is unique only at employer level.
        |
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
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ': Staff Number is required before the new member can be created.'
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
                . ' is already assigned to another current member under '
                . $batch->employer->name
                . '.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $nationalId =
            trim(
                (string) (
                    $data[
                        'national_id'
                    ]
                    ?? ''
                )
            );


        $normalizedNationalId =
            Member::normalizeNationalId(
                $nationalId !== ''
                    ? $nationalId
                    : null
            );


        /*
        |--------------------------------------------------------------------------
        | National ID Duplicate Check
        |--------------------------------------------------------------------------
        */

        if (
            $normalizedNationalId
        ) {
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
                $dateJoinedFund,
                $dateJoinedEmployer
            ): Member {

                /*
                |--------------------------------------------------------------------------
                | Allocate New Member Number
                |--------------------------------------------------------------------------
                |
                | A genuinely new member does not need a historical PenAd or
                | PENERP number in the Excel schedule.
                |
                | The new number is allocated here during POSTING.
                |
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
                    | Business rule for new members:
                    |
                    | PENERP NUMBER = PENAD NUMBER
                    |
                    */

                    'member_number' =>
                        $memberNumber,

                    'penad_member_number' =>
                        $memberNumber,

                    'fundworx_member_number' =>
                        $data[
                            'fundworx_member_number'
                        ]
                        ?? null,


                    /*
                    |--------------------------------------------------------------------------
                    | Member Name
                    |--------------------------------------------------------------------------
                    */

                    'surname' =>
                        $surname,

                    'first_names' =>
                        $firstNames,

                    'other_names' =>
                        $data[
                            'other_names'
                        ]
                        ?? null,


                    /*
                    |--------------------------------------------------------------------------
                    | Identity
                    |--------------------------------------------------------------------------
                    */

                    'national_id' =>
                        $nationalId !== ''
                            ? $nationalId
                            : null,

                    'national_id_normalized' =>
                        $normalizedNationalId,

                    'gender' =>
                        $data[
                            'gender'
                        ]
                        ?? null,

                    'date_of_birth' =>
                        $data[
                            'date_of_birth'
                        ]
                        ?? null,


                    /*
                    |--------------------------------------------------------------------------
                    | Fund Membership
                    |--------------------------------------------------------------------------
                    */

                    'date_joined_fund' =>
                        $dateJoinedFund,

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
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
                ]);


                $member->save();


                /*
                |--------------------------------------------------------------------------
                | Create Member Employment
                |--------------------------------------------------------------------------
                |
                | Actual member_employments columns:
                |
                | member_id
                | employer_id
                | staff_number
                | vote_number
                | branch
                | department
                | date_joined_employer
                | effective_from
                | effective_to
                | employment_status
                | is_current
                |
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
                        $data[
                            'vote_number'
                        ]
                        ?? null,

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


                    /*
                    |--------------------------------------------------------------------------
                    | Employment Dates
                    |--------------------------------------------------------------------------
                    */

                    'date_joined_employer' =>
                        $dateJoinedEmployer,

                    'effective_from' =>
                        $dateJoinedEmployer,

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
                        auth()->id(),

                    'updated_by' =>
                        auth()->id(),
                ]);


                $employment->save();


                /*
                |--------------------------------------------------------------------------
                | Update Contribution Staging Row
                |--------------------------------------------------------------------------
                */

                $normalizedData =
                    $row->normalized_data
                    ?? [];


                /*
                |--------------------------------------------------------------------------
                | Store Generated Numbers Back On Import Row
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
                | Mark Member Created
                |--------------------------------------------------------------------------
                */

                $row->update([
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


                return $member;
            }
        );
    }
}