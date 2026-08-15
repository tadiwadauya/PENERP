<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;

class ContributionMemberMatcher
{
    public function match(
        Employer $employer,
        array $data
    ): array {
        $matches =
            [];


        /*
        |--------------------------------------------------------------------------
        | PenAd Member Number
        |--------------------------------------------------------------------------
        |
        | The pension reference supplied on historical schedules is treated
        | first as the PenAd reference.
        |
        */

        $penadNumber =
            $this->clean(
                $data[
                    'pension_reference_number'
                ]
                ?? null
            );


        if ($penadNumber) {

            $member =
                Member::query()
                    ->where(
                        'penad_member_number',
                        $penadNumber
                    )
                    ->first();


            if ($member) {
                $matches[
                    'penad_number'
                ] =
                    $member;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PENERP Member Number
        |--------------------------------------------------------------------------
        */

        $penerpNumber =
            $this->clean(
                $data[
                    'penerp_member_number'
                ]
                ??
                $data[
                    'pension_reference_number'
                ]
                ??
                null
            );


        if ($penerpNumber) {

            $member =
                Member::query()
                    ->where(
                        'member_number',
                        $penerpNumber
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
        | Staff Number + Employer
        |--------------------------------------------------------------------------
        |
        | Staff number is NOT globally unique.
        |
        */

        $staffNumber =
            $this->clean(
                $data[
                    'staff_number'
                ]
                ?? null
            );


        if ($staffNumber) {

            $employments =
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
                    ->get();


            if (
                $employments->count()
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
                        'Staff number '
                        . $staffNumber
                        . ' is assigned to more than one current member under this employer.',
                ];
            }


            if (
                $employments->count()
                ===
                1
                &&
                $employments
                    ->first()
                    ->member
            ) {
                $matches[
                    'staff_number'
                ] =
                    $employments
                        ->first()
                        ->member;
            }
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


        if ($normalizedNationalId) {

            $members =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $normalizedNationalId
                    )
                    ->get();


            if (
                $members->count()
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
                        'National ID '
                        . (
                            $data[
                                'national_id'
                            ]
                            ?? ''
                        )
                        . ' is linked to more than one member.',
                ];
            }


            if (
                $members->count()
                ===
                1
            ) {
                $matches[
                    'national_id'
                ] =
                    $members->first();
            }
        }


        /*
        |--------------------------------------------------------------------------
        | No Match
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
                    'new_member',

                'conflict' =>
                    false,

                'message' =>
                    null,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Identifier Conflict Check
        |--------------------------------------------------------------------------
        |
        | If PenAd points to Member A while National ID points to Member B,
        | we must stop and require review.
        |
        */

        $uniqueMemberIds =
            collect(
                $matches
            )
                ->pluck(
                    'id'
                )
                ->unique()
                ->values();


        if (
            $uniqueMemberIds->count()
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
                    'Member identifiers conflict. Member number, staff number and/or National ID point to different existing members.',
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Priority
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
                    $matches[
                        $type
                    ]
                )
            ) {
                return [
                    'member' =>
                        $matches[
                            $type
                        ],

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
                'new_member',

            'conflict' =>
                false,

            'message' =>
                null,
        ];
    }


    private function clean(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            trim(
                (string) $value
            )
            === ''
        ) {
            return null;
        }


        return trim(
            (string) $value
        );
    }
}