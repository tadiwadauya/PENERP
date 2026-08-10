<?php

namespace App\Services\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Models\PensionsAdministration\Updates\MembershipImportRow;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MembershipImportService
{
    /*
    |--------------------------------------------------------------------------
    | Decisions Allowed Into Live Membership Register
    |--------------------------------------------------------------------------
    */

    private const IMPORTABLE_DECISIONS = [
        'create',
        'update',
        'use_existing',
        'ignore_warning',
    ];


    /*
    |--------------------------------------------------------------------------
    | Import Approved Membership Rows
    |--------------------------------------------------------------------------
    */

    public function import(
        MembershipImportBatch $batch
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Count Rows Ready for Import
        |--------------------------------------------------------------------------
        */

        $approvedRows = MembershipImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn(
                'review_decision',
                self::IMPORTABLE_DECISIONS
            )
            ->whereNull('imported_at')
            ->where('validation_status', '<>', 'error')
            ->count();


        if ($approvedRows === 0) {
            throw new RuntimeException(
                'There are no approved membership records available for import.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Start Batch
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' => 'importing',
            'approved_rows' => $approvedRows,
            'imported_rows' => 0,
            'progress_percentage' => 0,
            'failure_reason' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);


        $importedRows = 0;


        /*
        |--------------------------------------------------------------------------
        | Process in Database-Friendly Chunks
        |--------------------------------------------------------------------------
        */

        MembershipImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn(
                'review_decision',
                self::IMPORTABLE_DECISIONS
            )
            ->whereNull('imported_at')
            ->where('validation_status', '<>', 'error')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($rows) use (
                    $batch,
                    $approvedRows,
                    &$importedRows
                ) {
                    foreach ($rows as $row) {
                        $this->importRow(
                            $batch,
                            $row
                        );

                        $importedRows++;

                        /*
                        |--------------------------------------------------------------------------
                        | Progress Update
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $importedRows % 25 === 0
                            || $importedRows === $approvedRows
                        ) {
                            $percentage = round(
                                (
                                    $importedRows
                                    /
                                    max(1, $approvedRows)
                                )
                                * 100,
                                2
                            );


                            $batch->update([
                                'imported_rows' => $importedRows,
                                'progress_percentage' => min(
                                    100,
                                    $percentage
                                ),
                            ]);
                        }
                    }
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Complete Batch
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' => 'completed',
            'imported_rows' => $importedRows,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Import Individual Staging Row
    |--------------------------------------------------------------------------
    */

    private function importRow(
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): void {
        DB::transaction(function () use (
            $batch,
            $row
        ) {
            /*
            |--------------------------------------------------------------------------
            | Prevent Double Import
            |--------------------------------------------------------------------------
            */

            $row = MembershipImportRow::query()
                ->lockForUpdate()
                ->findOrFail(
                    $row->id
                );


            if ($row->imported_at) {
                return;
            }


            if (
                !in_array(
                    $row->review_decision,
                    self::IMPORTABLE_DECISIONS,
                    true
                )
            ) {
                throw new RuntimeException(
                    'Excel row '
                    . $row->row_number
                    . ' is not approved for import.'
                );
            }


            if (
                $row->validation_status === 'error'
            ) {
                throw new RuntimeException(
                    'Excel row '
                    . $row->row_number
                    . ' still contains validation errors.'
                );
            }


            $data = $this->normalizedData(
                $row
            );


            /*
            |--------------------------------------------------------------------------
            | Process Review Decision
            |--------------------------------------------------------------------------
            */

            $member = match (
                $row->review_decision
            ) {
                'create' =>
                    $this->createMember(
                        $row,
                        $data
                    ),

                'update' =>
                    $this->updateExistingMember(
                        $row,
                        $data
                    ),

                'use_existing' =>
                    $this->useExistingMember(
                        $row
                    ),

                'ignore_warning' =>
                    $this->importWarningApprovedMember(
                        $row,
                        $data
                    ),

                default =>
                    throw new RuntimeException(
                        'Unsupported review decision for Excel row '
                        . $row->row_number
                        . '.'
                    ),
            };


            /*
            |--------------------------------------------------------------------------
            | Employment Record
            |--------------------------------------------------------------------------
            |
            | use_existing deliberately does not overwrite employment unless
            | the staged record was approved for CREATE/UPDATE.
            |
            */

            if (
                $row->review_decision !== 'use_existing'
                && $row->matched_employer_id
            ) {
                $this->syncEmployment(
                    $member,
                    $row,
                    $data
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Mark Staging Row Imported
            |--------------------------------------------------------------------------
            */

            $row->update([
                'imported_member_id' =>
                    $member->id,

                'imported_at' =>
                    now(),
            ]);
        });
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE MEMBER
    |--------------------------------------------------------------------------
    */

    private function createMember(
        MembershipImportRow $row,
        array $data
    ): Member {
        /*
        |--------------------------------------------------------------------------
        | Safety Check
        |--------------------------------------------------------------------------
        */

        if ($row->matched_member_id) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ' is marked CREATE but already matches an existing member.'
            );
        }


        $memberNumber =
            $this->generateMemberNumber();


        return Member::create(
            $this->memberPayload(
                $data,
                $row,
                $memberNumber
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE EXISTING MEMBER
    |--------------------------------------------------------------------------
    */

    private function updateExistingMember(
        MembershipImportRow $row,
        array $data
    ): Member {
        if (!$row->matched_member_id) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ' is marked UPDATE but no existing member is linked.'
            );
        }


        $member = Member::query()
            ->lockForUpdate()
            ->findOrFail(
                $row->matched_member_id
            );


        $payload =
            $this->memberPayload(
                $data,
                $row,
                $member->member_number
            );


        /*
        |--------------------------------------------------------------------------
        | Never Regenerate Existing PENERP Member Number
        |--------------------------------------------------------------------------
        */

        unset(
            $payload['member_number'],
            $payload['created_by']
        );


        $member->update(
            $payload
        );


        return $member;
    }


    /*
    |--------------------------------------------------------------------------
    | USE EXISTING MEMBER
    |--------------------------------------------------------------------------
    |
    | No member data is overwritten.
    |
    */

    private function useExistingMember(
        MembershipImportRow $row
    ): Member {
        if (!$row->matched_member_id) {
            throw new RuntimeException(
                'Excel row '
                . $row->row_number
                . ' is marked USE EXISTING but no existing member is linked.'
            );
        }


        return Member::findOrFail(
            $row->matched_member_id
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVED WARNING
    |--------------------------------------------------------------------------
    |
    | If there is already a matched member, update it.
    |
    | Otherwise create a new member.
    |
    */

    private function importWarningApprovedMember(
        MembershipImportRow $row,
        array $data
    ): Member {
        if ($row->matched_member_id) {
            return $this->updateExistingMember(
                $row,
                $data
            );
        }


        return $this->createMember(
            $row,
            $data
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Member Payload
    |--------------------------------------------------------------------------
    */

    private function memberPayload(
        array $data,
        MembershipImportRow $row,
        string $memberNumber
    ): array {
        $status =
            strtolower(
                trim(
                    (string)
                    (
                        $data['membership_status']
                        ?? 'inactive'
                    )
                )
            );


        return [
            /*
            |--------------------------------------------------------------------------
            | References
            |--------------------------------------------------------------------------
            */

            'member_number' =>
                $memberNumber,

            'penad_member_number' =>
                $this->nullableString(
                    $data['penad_member_number']
                    ?? null
                ),

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

            'title' =>
                $this->nullableString(
                    $data['title']
                    ?? null
                ),

            'surname' =>
                $this->requiredString(
                    $data['surname']
                    ?? null,
                    'Surname',
                    $row
                ),

            'first_names' =>
                $this->requiredString(
                    $data['first_names']
                    ?? null,
                    'First names',
                    $row
                ),

            'other_names' =>
                $this->nullableString(
                    $data['other_names']
                    ?? null
                ),

            'maiden_name' =>
                $this->nullableString(
                    $data['maiden_name']
                    ?? null
                ),


            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'national_id' =>
                $this->nullableString(
                    $data['national_id']
                    ?? null
                ),

            'national_id_normalized' =>
                $this->nullableString(
                    $data['national_id_normalized']
                    ?? null
                ),

            'date_of_birth' =>
                $data['date_of_birth']
                ?? null,

            'gender' =>
                $this->nullableString(
                    $data['gender']
                    ?? null
                ),

            'marital_status' =>
                $this->nullableString(
                    $data['marital_status']
                    ?? null
                ),

            'occupation' =>
                $this->nullableString(
                    $data['occupation']
                    ?? null
                ),


            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            'email' =>
                $this->nullableString(
                    $data['email']
                    ?? null
                ),

            'secondary_email' =>
                $this->nullableString(
                    $data['secondary_email']
                    ?? null
                ),

            'cell_number' =>
                $this->nullableString(
                    $data['cell_number']
                    ?? null
                ),

            'secondary_cell_number' =>
                $this->nullableString(
                    $data['secondary_cell_number']
                    ?? null
                ),


            /*
            |--------------------------------------------------------------------------
            | Physical Address
            |--------------------------------------------------------------------------
            */

            'physical_address_1' =>
                $this->nullableString(
                    $data['physical_address_1']
                    ?? null
                ),

            'physical_address_2' =>
                $this->nullableString(
                    $data['physical_address_2']
                    ?? null
                ),

            'physical_address_3' =>
                $this->nullableString(
                    $data['physical_address_3']
                    ?? null
                ),

            'physical_suburb' =>
                $this->nullableString(
                    $data['physical_suburb']
                    ?? null
                ),

            'physical_city' =>
                $this->nullableString(
                    $data['physical_city']
                    ?? null
                ),

            'physical_country' =>
                $this->nullableString(
                    $data['physical_country']
                    ?? null
                ),


            /*
            |--------------------------------------------------------------------------
            | Postal Address
            |--------------------------------------------------------------------------
            */

            'postal_address_1' =>
                $this->nullableString(
                    $data['postal_address_1']
                    ?? null
                ),

            'postal_address_2' =>
                $this->nullableString(
                    $data['postal_address_2']
                    ?? null
                ),

            'postal_address_3' =>
                $this->nullableString(
                    $data['postal_address_3']
                    ?? null
                ),

            'postal_city' =>
                $this->nullableString(
                    $data['postal_city']
                    ?? null
                ),

            'postal_country' =>
                $this->nullableString(
                    $data['postal_country']
                    ?? null
                ),


            /*
            |--------------------------------------------------------------------------
            | Membership
            |--------------------------------------------------------------------------
            */

            'date_joined_fund' =>
                $data['date_joined_fund']
                ?? null,

            'membership_status' =>
                $status,

            'is_active' =>
                $status === 'active',


            /*
            |--------------------------------------------------------------------------
            | Audit User
            |--------------------------------------------------------------------------
            */

            'created_by' =>
                $row->reviewed_by,

            'updated_by' =>
                $row->reviewed_by,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Employment
    |--------------------------------------------------------------------------
    */

    private function syncEmployment(
        Member $member,
        MembershipImportRow $row,
        array $data
    ): void {
        $employerId =
            (int)
            $row->matched_employer_id;


        /*
        |--------------------------------------------------------------------------
        | Find Current Employment for This Employer
        |--------------------------------------------------------------------------
        */

        $employment = MemberEmployment::query()
            ->where(
                'member_id',
                $member->id
            )
            ->where(
                'employer_id',
                $employerId
            )
            ->where(
                'is_current',
                true
            )
            ->first();


        /*
        |--------------------------------------------------------------------------
        | If Member Currently Belongs to Another Employer
        |--------------------------------------------------------------------------
        |
        | Close the previous current employment relationship.
        |
        */

        MemberEmployment::query()
            ->where(
                'member_id',
                $member->id
            )
            ->where(
                'is_current',
                true
            )
            ->where(
                'employer_id',
                '<>',
                $employerId
            )
            ->update([
                'is_current' => false,
                'updated_by' => $row->reviewed_by,
                'updated_at' => now(),
            ]);


        $status =
            strtolower(
                trim(
                    (string)
                    (
                        $data['membership_status']
                        ?? 'inactive'
                    )
                )
            );


        $payload = [
            'staff_number' =>
                $this->nullableString(
                    $data['staff_number']
                    ?? null
                ),

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
                $data['date_joined_employer']
                ?? null,

            'effective_from' =>
                $data['date_joined_employer']
                ?? $data['date_joined_fund']
                ?? null,

            'effective_to' =>
                null,

            'employment_status' =>
                $status,

            'is_current' =>
                true,

            'updated_by' =>
                $row->reviewed_by,
        ];


        if ($employment) {
            $employment->update(
                $payload
            );

            return;
        }


        MemberEmployment::create(
            array_merge(
                $payload,
                [
                    'member_id' =>
                        $member->id,

                    'employer_id' =>
                        $employerId,

                    'created_by' =>
                        $row->reviewed_by,
                ]
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Generate PENERP Member Number
    |--------------------------------------------------------------------------
    |
    | Examples:
    |
    | PEN00000001
    | PEN00000002
    | PEN00000003
    |
    | SQL Server locking prevents concurrent imports from obtaining the same
    | next number.
    |
    */

    private function generateMemberNumber(): string
    {
        $last = DB::selectOne(
            "
            SELECT TOP 1
                member_number
            FROM members WITH (UPDLOCK, HOLDLOCK)
            WHERE member_number LIKE 'PEN%'
              AND TRY_CONVERT(
                    BIGINT,
                    SUBSTRING(
                        member_number,
                        4,
                        50
                    )
                  ) IS NOT NULL
            ORDER BY TRY_CONVERT(
                BIGINT,
                SUBSTRING(
                    member_number,
                    4,
                    50
                )
            ) DESC
            "
        );


        $lastNumber = 0;


        if (
            $last
            && !empty(
                $last->member_number
            )
        ) {
            $numericPart =
                substr(
                    $last->member_number,
                    3
                );


            if (
                ctype_digit(
                    $numericPart
                )
            ) {
                $lastNumber =
                    (int)
                    $numericPart;
            }
        }


        $nextNumber =
            $lastNumber + 1;


        return
            'PEN'
            . str_pad(
                (string)
                $nextNumber,
                8,
                '0',
                STR_PAD_LEFT
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalized Staging Data
    |--------------------------------------------------------------------------
    */

    private function normalizedData(
        MembershipImportRow $row
    ): array {
        $data =
            $row->normalized_data;


        if (is_array($data)) {
            return $data;
        }


        if (is_string($data)) {
            $decoded =
                json_decode(
                    $data,
                    true
                );


            if (
                json_last_error()
                ===
                JSON_ERROR_NONE
                && is_array(
                    $decoded
                )
            ) {
                return $decoded;
            }
        }


        throw new RuntimeException(
            'Normalized membership data could not be read for Excel row '
            . $row->row_number
            . '.'
        );
    }


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
            $value === ''
                ? null
                : $value;
    }


    private function requiredString(
        mixed $value,
        string $field,
        MembershipImportRow $row
    ): string {
        $value =
            $this->nullableString(
                $value
            );


        if ($value === null) {
            throw new RuntimeException(
                $field
                . ' is missing on Excel row '
                . $row->row_number
                . '.'
            );
        }


        return $value;
    }
}