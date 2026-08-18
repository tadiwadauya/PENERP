<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MemberNumberSequenceSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Sequence Code
        |--------------------------------------------------------------------------
        */

        $sequenceCode =
            'PENERP_MEMBER_NUMBER';


        /*
        |--------------------------------------------------------------------------
        | Existing Sequence
        |--------------------------------------------------------------------------
        */

        $existingSequence =
            DB::table(
                'member_number_sequences'
            )
                ->where(
                    'sequence_code',
                    $sequenceCode
                )
                ->first();


        if ($existingSequence) {

            $this->command?->info(
                'PENERP member number sequence already exists. Current last number: '
                . $existingSequence->last_number
            );

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Determine Highest Existing Member Number
        |--------------------------------------------------------------------------
        |
        | SQL Server TRY_CONVERT allows us to ignore any historic member
        | references that are not purely numeric.
        |
        */

        $maximumMemberNumber =
            DB::table(
                'members'
            )
                ->selectRaw(
                    '
                    MAX(
                        TRY_CONVERT(
                            BIGINT,
                            member_number
                        )
                    ) AS maximum_member_number
                    '
                )
                ->value(
                    'maximum_member_number'
                );


        /*
        |--------------------------------------------------------------------------
        | Also Check Existing PenAd Numbers
        |--------------------------------------------------------------------------
        */

        $maximumPenadNumber =
            DB::table(
                'members'
            )
                ->selectRaw(
                    '
                    MAX(
                        TRY_CONVERT(
                            BIGINT,
                            penad_member_number
                        )
                    ) AS maximum_penad_number
                    '
                )
                ->value(
                    'maximum_penad_number'
                );


        /*
        |--------------------------------------------------------------------------
        | Take Highest Number From Either Field
        |--------------------------------------------------------------------------
        */

        $currentMaximum =
            max(
                (int) (
                    $maximumMemberNumber
                    ?? 0
                ),
                (int) (
                    $maximumPenadNumber
                    ?? 0
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Safety Check
        |--------------------------------------------------------------------------
        */

        if (
            $currentMaximum < 0
        ) {
            throw new RuntimeException(
                'Unable to determine a valid starting PENERP member number.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Create Sequence
        |--------------------------------------------------------------------------
        |
        | last_number represents the highest number ALREADY allocated.
        |
        | The next generated member receives:
        |
        | last_number + 1
        |
        */

        DB::table(
            'member_number_sequences'
        )->insert([
            'sequence_code' =>
                $sequenceCode,

            'last_number' =>
                $currentMaximum,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ]);


        $this->command?->info(
            'PENERP member number sequence initialized at '
            . $currentMaximum
            . '. Next new member number will be '
            . (
                $currentMaximum
                +
                1
            )
            . '.'
        );
    }
}