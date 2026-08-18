<?php

namespace App\Services\PensionsAdministration\Updates;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class MemberNumberGeneratorService
{
    private const SEQUENCE_CODE =
        'PENERP_MEMBER_NUMBER';


    /*
    |--------------------------------------------------------------------------
    | Generate Next PENERP Member Number
    |--------------------------------------------------------------------------
    |
    | New members receive:
    |
    | PENERP Number = generated number
    | PenAd Number  = same generated number
    |
    */

    public function next(): string
    {
        return DB::transaction(
            function (): string {

                /*
                |--------------------------------------------------------------------------
                | Lock Existing Sequence
                |--------------------------------------------------------------------------
                */

                $sequence =
                    DB::table(
                        'member_number_sequences'
                    )
                        ->where(
                            'sequence_code',
                            self::SEQUENCE_CODE
                        )
                        ->lockForUpdate()
                        ->first();


                /*
                |--------------------------------------------------------------------------
                | First Use
                |--------------------------------------------------------------------------
                |
                | If the sequence has never been used before, determine the highest
                | numeric member number already existing in the members table.
                |
                | SQL Server TRY_CONVERT prevents old non-numeric references from
                | breaking the query.
                |
                */

                if (!$sequence) {

                    $currentMaximum =
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


                    $currentMaximum =
                        (int) (
                            $currentMaximum
                            ?? 0
                        );


                    DB::table(
                        'member_number_sequences'
                    )->insert([
                        'sequence_code' =>
                            self::SEQUENCE_CODE,

                        'last_number' =>
                            $currentMaximum,

                        'created_at' =>
                            now(),

                        'updated_at' =>
                            now(),
                    ]);


                    /*
                    |--------------------------------------------------------------------------
                    | Re-lock Newly Created Sequence
                    |--------------------------------------------------------------------------
                    */

                    $sequence =
                        DB::table(
                            'member_number_sequences'
                        )
                            ->where(
                                'sequence_code',
                                self::SEQUENCE_CODE
                            )
                            ->lockForUpdate()
                            ->first();
                }


                if (!$sequence) {
                    throw new RuntimeException(
                        'The PENERP member number sequence could not be initialized.'
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Next Number
                |--------------------------------------------------------------------------
                */

                $nextNumber =
                    (int)
                    $sequence->last_number
                    +
                    1;


                /*
                |--------------------------------------------------------------------------
                | Ensure Number Is Not Already Used
                |--------------------------------------------------------------------------
                */

                while (
                    DB::table(
                        'members'
                    )
                        ->where(
                            'member_number',
                            (string)
                            $nextNumber
                        )
                        ->orWhere(
                            'penad_member_number',
                            (string)
                            $nextNumber
                        )
                        ->exists()
                ) {
                    $nextNumber++;
                }


                /*
                |--------------------------------------------------------------------------
                | Update Sequence
                |--------------------------------------------------------------------------
                */

                DB::table(
                    'member_number_sequences'
                )
                    ->where(
                        'sequence_code',
                        self::SEQUENCE_CODE
                    )
                    ->update([
                        'last_number' =>
                            $nextNumber,

                        'updated_at' =>
                            now(),
                    ]);


                return (string)
                    $nextNumber;
            }
        );
    }
}