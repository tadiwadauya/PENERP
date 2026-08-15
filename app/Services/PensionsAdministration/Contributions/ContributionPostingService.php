<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\MemberContribution;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ContributionPostingService
{
    public function post(
        ContributionImportBatch $batch
    ): void {
        $batch->load([
            'contributionPeriod',
            'rows.matchedMember',
            'rows.createdMember',
        ]);


        if (
            $batch->status !==
            'posting'
        ) {
            throw new RuntimeException(
                'The contribution batch is not ready for posting.'
            );
        }


        if (
            !$batch->approved_at
        ) {
            throw new RuntimeException(
                'The contribution batch has not been approved.'
            );
        }


        if (
            $batch->error_rows
            >
            0
        ) {
            throw new RuntimeException(
                'Contribution batch contains unresolved errors.'
            );
        }


        DB::transaction(
            function () use (
                $batch
            ): void {

                $rows =
                    $batch
                        ->rows()
                        ->whereIn(
                            'validation_status',
                            [
                                'valid',
                                'warning',
                            ]
                        )
                        ->orderBy(
                            'row_number'
                        )
                        ->get();


                $posted =
                    0;


                foreach (
                    $rows
                    as $row
                ) {

                    $member =
                        $row
                            ->createdMember
                        ??
                        $row
                            ->matchedMember;


                    if (!$member) {
                        throw new RuntimeException(
                            'No member is linked to Excel row '
                            . $row->row_number
                            . '.'
                        );
                    }


                    $data =
                        $row
                            ->normalized_data
                        ??
                        [];


                    $transactionType =
                        $this->containsNegativeAmount(
                            $data
                        )
                            ? 'adjustment'
                            : 'expected';


                    MemberContribution::create([
                        'member_id' =>
                            $member->id,

                        'employer_id' =>
                            $batch
                                ->employer_id,

                        'contribution_period_id' =>
                            $batch
                                ->contribution_period_id,

                        'import_batch_id' =>
                            $batch->id,

                        'import_row_id' =>
                            $row->id,

                        'source_row_number' =>
                            $row
                                ->row_number,

                        'source_system' =>
                            'PENERP_CONTRIBUTION_IMPORT',

                        'penerp_member_number' =>
                            $member
                                ->member_number,

                        'penad_member_number' =>
                            $member
                                ->penad_member_number,

                        'fundworx_member_number' =>
                            $member
                                ->fundworx_member_number,

                        'staff_number' =>
                            $data[
                                'staff_number'
                            ]
                            ?? null,

                        'period_date' =>
                            $batch
                                ->contributionPeriod
                                ->period_date,

                        'period_year' =>
                            $batch
                                ->contributionPeriod
                                ->period_year,

                        'period_month' =>
                            $batch
                                ->contributionPeriod
                                ->period_month,

                        'due_date' =>
                            $data[
                                'due_date'
                            ]
                            ?? null,

                        'scheme_code' =>
                            $data[
                                'scheme_code'
                            ]
                            ?? null,

                        'transaction_type' =>
                            $transactionType,

                        'payment_flag' =>
                            $data[
                                'payment_flag'
                            ]
                            ?? null,

                        'usd_basic_pay' =>
                            $data[
                                'usd_basic_pay'
                            ]
                            ?? 0,

                        'usd_employee_rate' =>
                            $data[
                                'usd_employee_rate'
                            ]
                            ?? 0,

                        'usd_employer_rate' =>
                            $data[
                                'usd_employer_rate'
                            ]
                            ?? 0,

                        'usd_employee_contribution' =>
                            $data[
                                'usd_employee_contribution'
                            ]
                            ?? 0,

                        'usd_employer_contribution' =>
                            $data[
                                'usd_employer_contribution'
                            ]
                            ?? 0,

                        'usd_employee_avc' =>
                            $data[
                                'usd_employee_avc'
                            ]
                            ?? 0,

                        'usd_employer_avc' =>
                            $data[
                                'usd_employer_avc'
                            ]
                            ?? 0,

                        'usd_employee_arrear' =>
                            $data[
                                'usd_employee_arrear'
                            ]
                            ?? 0,

                        'usd_employer_arrear' =>
                            $data[
                                'usd_employer_arrear'
                            ]
                            ?? 0,

                        'usd_employee_transfer_in' =>
                            $data[
                                'usd_employee_transfer_in'
                            ]
                            ?? 0,

                        'usd_employer_transfer_in' =>
                            $data[
                                'usd_employer_transfer_in'
                            ]
                            ?? 0,

                        'usd_employee_late_interest' =>
                            $data[
                                'usd_employee_late_interest'
                            ]
                            ?? 0,

                        'usd_employer_late_interest' =>
                            $data[
                                'usd_employer_late_interest'
                            ]
                            ?? 0,

                        'zwg_basic_pay' =>
                            $data[
                                'zwg_basic_pay'
                            ]
                            ?? 0,

                        'zwg_employee_rate' =>
                            $data[
                                'zwg_employee_rate'
                            ]
                            ?? 0,

                        'zwg_employer_rate' =>
                            $data[
                                'zwg_employer_rate'
                            ]
                            ?? 0,

                        'zwg_employee_contribution' =>
                            $data[
                                'zwg_employee_contribution'
                            ]
                            ?? 0,

                        'zwg_employer_contribution' =>
                            $data[
                                'zwg_employer_contribution'
                            ]
                            ?? 0,

                        'zwg_employee_avc' =>
                            $data[
                                'zwg_employee_avc'
                            ]
                            ?? 0,

                        'zwg_employer_avc' =>
                            $data[
                                'zwg_employer_avc'
                            ]
                            ?? 0,

                        'zwg_employee_arrear' =>
                            $data[
                                'zwg_employee_arrear'
                            ]
                            ?? 0,

                        'zwg_employer_arrear' =>
                            $data[
                                'zwg_employer_arrear'
                            ]
                            ?? 0,

                        'zwg_employee_transfer_in' =>
                            $data[
                                'zwg_employee_transfer_in'
                            ]
                            ?? 0,

                        'zwg_employer_transfer_in' =>
                            $data[
                                'zwg_employer_transfer_in'
                            ]
                            ?? 0,

                        'zwg_employee_late_interest' =>
                            $data[
                                'zwg_employee_late_interest'
                            ]
                            ?? 0,

                        'zwg_employer_late_interest' =>
                            $data[
                                'zwg_employer_late_interest'
                            ]
                            ?? 0,

                        'comments' =>
                            $data[
                                'comments'
                            ]
                            ?? null,

                        'posted_by' =>
                            $batch
                                ->posted_by,

                        'posted_at' =>
                            now(),

                        'created_by' =>
                            $batch
                                ->posted_by,

                        'updated_by' =>
                            $batch
                                ->posted_by,
                    ]);


                    $posted++;


                    $batch->update([
                        'posted_rows' =>
                            $posted,

                        'progress_percentage' =>
                            min(
                                99,
                                (
                                    $posted
                                    /
                                    max(
                                        1,
                                        $rows->count()
                                    )
                                )
                                *
                                100
                            ),
                    ]);
                }


                $batch->update([
                    'status' =>
                        'posted',

                    'posted_rows' =>
                        $posted,

                    'posted_at' =>
                        now(),

                    'progress_percentage' =>
                        100,
                ]);


                $batch
                    ->contributionPeriod
                    ->update([
                        'status' =>
                            'posted',

                        'updated_by' =>
                            $batch
                                ->posted_by,
                    ]);
            }
        );
    }


    private function containsNegativeAmount(
        array $data
    ): bool {
        $values = [
            $data[
                'usd_employee_contribution'
            ] ?? 0,

            $data[
                'usd_employer_contribution'
            ] ?? 0,

            $data[
                'usd_employee_avc'
            ] ?? 0,

            $data[
                'usd_employer_avc'
            ] ?? 0,

            $data[
                'zwg_employee_contribution'
            ] ?? 0,

            $data[
                'zwg_employer_contribution'
            ] ?? 0,

            $data[
                'zwg_employee_avc'
            ] ?? 0,

            $data[
                'zwg_employer_avc'
            ] ?? 0,
        ];


        return collect(
            $values
        )->contains(
            fn ($value) =>
                (float) $value
                <
                0
        );
    }
}