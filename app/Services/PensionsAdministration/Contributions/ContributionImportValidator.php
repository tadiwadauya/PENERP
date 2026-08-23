<?php

namespace App\Services\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ContributionImportValidator
{
    public function __construct(
        private readonly ContributionExcelReader $excelReader,
        private readonly ContributionMemberMatcher $memberMatcher
    ) {
    }

    public function process(ContributionImportBatch $batch): void
    {
        $batch->load([
            'employer',
            'contributionPeriod',
        ]);

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
        | Contribution Period Integrity
        |--------------------------------------------------------------------------
        */

        $periodYear = (int) $batch->contributionPeriod->period_year;
        $periodMonth = (int) $batch->contributionPeriod->period_month;

        if (
            $periodYear < 2000
            ||
            $periodYear > 2100
            ||
            $periodMonth < 1
            ||
            $periodMonth > 12
        ) {
            throw new RuntimeException(
                'The contribution batch is linked to an invalid contribution period.'
            );
        }

        try {
            $periodDate = Carbon::parse(
                $batch->contributionPeriod->period_date
            );

            if (
                $periodDate->year !== $periodYear
                ||
                $periodDate->month !== $periodMonth
            ) {
                throw new RuntimeException(
                    'The contribution period is internally inconsistent. '
                    . 'The stored period date is '
                    . $periodDate->format('d M Y')
                    . ', but the stored contribution month is '
                    . Carbon::create(
                        $periodYear,
                        $periodMonth,
                        1
                    )->format('F Y')
                    . '.'
                );
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable) {
            throw new RuntimeException(
                'The contribution period date could not be interpreted.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        $currency = strtoupper(
            trim(
                (string) (
                    $batch->currency_code
                    ?? 'ZWG'
                )
            )
        );

        if (
            !in_array(
                $currency,
                ['ZWG', 'USD'],
                true
            )
        ) {
            throw new RuntimeException(
                'Unsupported contribution currency: '
                . $currency
                . '. Only ZWG and USD contribution schedules are currently supported.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Start Processing
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'status' => 'processing',
            'progress_percentage' => 5,
            'total_rows' => 0,
            'processed_rows' => 0,
            'valid_rows' => 0,
            'warning_rows' => 0,
            'error_rows' => 0,
            'existing_member_rows' => 0,
            'new_member_rows' => 0,
            'nil_contributor_rows' => 0,
            'failure_reason' => null,
            'processing_started_at' => now(),
            'completed_at' => null,
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | Resolve Uploaded Excel File
            |--------------------------------------------------------------------------
            */

            $disk = Storage::disk('local');

            if (!$disk->exists($batch->file_path)) {
                throw new RuntimeException(
                    'The contribution Excel file could not be found at the stored location: '
                    . $batch->file_path
                );
            }

            $fullPath = $disk->path($batch->file_path);

            $batch->update([
                'progress_percentage' => 8,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Inspect Workbook
            |--------------------------------------------------------------------------
            */

            $excel = $this->excelReader->inspect(
                $fullPath
            );

            $estimatedRows = (int) (
                $excel['estimated_rows']
                ?? 0
            );

            if ($estimatedRows <= 0) {
                throw new RuntimeException(
                    'The contribution Excel file does not contain any contribution rows.'
                );
            }

            $batch->update([
                'total_rows' => $estimatedRows,
                'progress_percentage' => 15,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Clear Previous Validation Results
            |--------------------------------------------------------------------------
            */

            ContributionPeriodMemberStatus::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();

            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();

            $batch->update([
                'progress_percentage' => 20,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $processedRows = 0;
            $validRows = 0;
            $warningRows = 0;
            $errorRows = 0;
            $existingMemberRows = 0;
            $newMemberRows = 0;

            $scheduledMemberIds = [];
            $seenFingerprints = [];

            /*
            |--------------------------------------------------------------------------
            | Batch Totals
            |--------------------------------------------------------------------------
            */

            $totals = [
                'usd_basic_pay_total' => 0.0,
                'usd_employee_contribution_total' => 0.0,
                'usd_employer_contribution_total' => 0.0,
                'usd_employee_avc_total' => 0.0,
                'usd_employer_avc_total' => 0.0,

                'zwg_basic_pay_total' => 0.0,
                'zwg_employee_contribution_total' => 0.0,
                'zwg_employer_contribution_total' => 0.0,
                'zwg_employee_avc_total' => 0.0,
                'zwg_employer_avc_total' => 0.0,
            ];

            /*
            |--------------------------------------------------------------------------
            | Stream And Validate Rows
            |--------------------------------------------------------------------------
            */

            foreach (
                $this->excelReader->rows(
                    $fullPath,
                    $excel
                )
                as $excelRow
            ) {
                $processedRows++;

                $data = $excelRow['normalized_data'] ?? [];

                $data = $this->mapCurrencyValues(
                    $currency,
                    $data
                );

                $errors = [];
                $warnings = [];

                /*
                |--------------------------------------------------------------------------
                | Required Member Data
                |--------------------------------------------------------------------------
                */

                $this->validateRequiredMemberData(
                    $data,
                    $errors
                );

                /*
                |--------------------------------------------------------------------------
                | Employer Reference
                |--------------------------------------------------------------------------
                */

                $this->validateEmployerReference(
                    $batch,
                    $data,
                    $errors
                );

                /*
                |--------------------------------------------------------------------------
                | Contribution Period
                |--------------------------------------------------------------------------
                */

                $this->validatePeriod(
                    $batch,
                    $data,
                    $warnings
                );

                /*
                |--------------------------------------------------------------------------
                | Duplicate Row Detection
                |--------------------------------------------------------------------------
                */

                $fingerprint = $this->makeFingerprint(
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
                        . $seenFingerprints[$fingerprint]
                        . '.';
                } else {
                    $seenFingerprints[$fingerprint] = (int) (
                        $excelRow['row_number']
                        ?? 0
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Member Matching
                |--------------------------------------------------------------------------
                */

                $match = $this->memberMatcher->match(
                    $batch->employer,
                    $data
                );

                $member = $match['member'] ?? null;
                $matchType = $match['match_type'] ?? null;
                $isNewMember = false;

                if ($match['conflict'] ?? false) {
                    $errors[] =
                        $match['message']
                        ?? 'The member identifiers supplied in this row conflict.';
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Member
                |--------------------------------------------------------------------------
                */

                if ($member) {
                    $existingMemberRows++;

                    $scheduledMemberIds[
                        $member->id
                    ] = true;

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

                    ContributionPeriodMemberStatus::updateOrCreate(
                        [
                            'contribution_period_id' =>
                                $batch->contribution_period_id,

                            'member_id' =>
                                $member->id,
                        ],
                        [
                            'employer_id' =>
                                $batch->employer_id,

                            'contribution_status' =>
                                'contributed',

                            'reason' =>
                                'Member appears on the monthly expected contribution schedule.',

                            'import_batch_id' =>
                                $batch->id,
                        ]
                    );
                } elseif (
                    !(
                        $match['conflict']
                        ?? false
                    )
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Proposed New Member
                    |--------------------------------------------------------------------------
                    */

                    $isNewMember = true;
                    $matchType = 'new_member';

                    $this->validateNewMemberCandidate(
                        $batch,
                        $data,
                        $errors,
                        $warnings
                    );

                    if (empty($errors)) {
                        $newMemberRows++;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Financial Validation
                |--------------------------------------------------------------------------
                */

                $this->validateFinancialValues(
                    currency: $currency,
                    data: $data,
                    isNewMember: $isNewMember,
                    warnings: $warnings
                );

                $errors = array_values(
                    array_unique($errors)
                );

                $warnings = array_values(
                    array_unique($warnings)
                );

                /*
                |--------------------------------------------------------------------------
                | Validation Status
                |--------------------------------------------------------------------------
                */

                if (!empty($errors)) {
                    $validationStatus = 'error';
                    $errorRows++;
                } elseif (!empty($warnings)) {
                    $validationStatus = 'warning';
                    $warningRows++;
                } else {
                    $validationStatus = 'valid';
                    $validRows++;
                }

                /*
                |--------------------------------------------------------------------------
                | Batch Totals
                |--------------------------------------------------------------------------
                */

                $this->addTotals(
                    $totals,
                    $data
                );

                /*
                |--------------------------------------------------------------------------
                | Store Staging Row
                |--------------------------------------------------------------------------
                */

                ContributionImportRow::create([
                    'import_batch_id' =>
                        $batch->id,

                    'row_number' =>
                        (int) (
                            $excelRow['row_number']
                            ?? 0
                        ),

                    'raw_data' =>
                        $excelRow['raw_data']
                        ?? [],

                    'normalized_data' =>
                        $data,

                    'matched_member_id' =>
                        $member?->id,

                    'match_type' =>
                        $matchType,

                    'is_new_member' =>
                        $isNewMember,

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

                $physicalRowsRead = max(
                    1,
                    ((int) ($excelRow['row_number'] ?? 2)) - 1
                );

                $rowProgressRatio = min(
                    1,
                    $physicalRowsRead
                    /
                    max(
                        1,
                        $estimatedRows
                    )
                );

                $progressPercentage =
                    20
                    +
                    ($rowProgressRatio * 60);

                $progressUpdateInterval = max(
                    1,
                    (int) ceil(
                        $estimatedRows / 100
                    )
                );

                if (
                    $processedRows === 1
                    ||
                    $physicalRowsRead >= $estimatedRows
                    ||
                    $physicalRowsRead % $progressUpdateInterval === 0
                ) {
                    $batch->update([
                        'processed_rows' =>
                            $processedRows,

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

                        'progress_percentage' =>
                            round(
                                min(
                                    80,
                                    $progressPercentage
                                ),
                                2
                            ),
                    ]);
                }

                unset(
                    $data,
                    $errors,
                    $warnings,
                    $match,
                    $member,
                    $excelRow
                );
            }

            /*
            |--------------------------------------------------------------------------
            | No Usable Rows
            |--------------------------------------------------------------------------
            */

            if ($processedRows === 0) {
                throw new RuntimeException(
                    'The contribution Excel file does not contain any usable contribution rows.'
                );
            }

            $totalRows = $processedRows;

            $batch->update([
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

                'progress_percentage' =>
                    82,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Nil Contributors
            |--------------------------------------------------------------------------
            */

            $scheduledIds = array_map(
                'intval',
                array_keys(
                    $scheduledMemberIds
                )
            );

            $nilContributorCount = $this->identifyNilContributors(
                $batch,
                $scheduledIds
            );

            $batch->update([
                'nil_contributor_rows' =>
                    $nilContributorCount,

                'progress_percentage' =>
                    90,
            ]);

            $uniqueScheduledMembers = count(
                $scheduledMemberIds
            );

            /*
            |--------------------------------------------------------------------------
            | Save Totals
            |--------------------------------------------------------------------------
            */

            $batch->update([
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

                'usd_basic_pay_total' =>
                    $totals['usd_basic_pay_total'],

                'usd_employee_contribution_total' =>
                    $totals['usd_employee_contribution_total'],

                'usd_employer_contribution_total' =>
                    $totals['usd_employer_contribution_total'],

                'usd_employee_avc_total' =>
                    $totals['usd_employee_avc_total'],

                'usd_employer_avc_total' =>
                    $totals['usd_employer_avc_total'],

                'zwg_basic_pay_total' =>
                    $totals['zwg_basic_pay_total'],

                'zwg_employee_contribution_total' =>
                    $totals['zwg_employee_contribution_total'],

                'zwg_employer_contribution_total' =>
                    $totals['zwg_employer_contribution_total'],

                'zwg_employee_avc_total' =>
                    $totals['zwg_employee_avc_total'],

                'zwg_employer_avc_total' =>
                    $totals['zwg_employer_avc_total'],

                'progress_percentage' =>
                    94,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Update Contribution Period
            |--------------------------------------------------------------------------
            */

            $batch->contributionPeriod->update([
                'status' =>
                    'awaiting_review',

                'scheduled_members' =>
                    $uniqueScheduledMembers
                    +
                    $newMemberRows,

                'existing_members' =>
                    $uniqueScheduledMembers,

                'new_members' =>
                    $newMemberRows,

                'nil_contributors' =>
                    $nilContributorCount,

                'updated_by' =>
                    $batch->uploaded_by,
            ]);

            $batch->update([
                'progress_percentage' =>
                    98,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Validation Complete
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'status' =>
                    'awaiting_review',

                'progress_percentage' =>
                    100,

                'completed_at' =>
                    now(),

                'failure_reason' =>
                    null,
            ]);

            unset(
                $excel,
                $scheduledMemberIds,
                $scheduledIds,
                $seenFingerprints
            );

            gc_collect_cycles();

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
    | Map Currency Values
    |--------------------------------------------------------------------------
    */

    private function mapCurrencyValues(
        string $currency,
        array $data
    ): array {
        $mapped = [
            ...$data,

            'usd_basic_pay' =>
                (float) ($data['usd_basic_pay'] ?? 0),

            'usd_employee_rate' =>
                $this->normalisePercentageRate(
                    $data['usd_employee_rate'] ?? 0
                ),

            'usd_employer_rate' =>
                $this->normalisePercentageRate(
                    $data['usd_employer_rate'] ?? 0
                ),

            'usd_employee_contribution' =>
                (float) ($data['usd_employee_contribution'] ?? 0),

            'usd_employer_contribution' =>
                (float) ($data['usd_employer_contribution'] ?? 0),

            'usd_employee_avc' =>
                (float) ($data['usd_employee_avc'] ?? 0),

            'usd_employer_avc' =>
                (float) ($data['usd_employer_avc'] ?? 0),

            'usd_employee_arrear' =>
                (float) ($data['usd_employee_arrear'] ?? 0),

            'usd_employer_arrear' =>
                (float) ($data['usd_employer_arrear'] ?? 0),

            'usd_employee_transfer_in' =>
                (float) ($data['usd_employee_transfer_in'] ?? 0),

            'usd_employer_transfer_in' =>
                (float) ($data['usd_employer_transfer_in'] ?? 0),

            'usd_employee_late_interest' =>
                (float) ($data['usd_employee_late_interest'] ?? 0),

            'usd_employer_late_interest' =>
                (float) ($data['usd_employer_late_interest'] ?? 0),

            'zwg_basic_pay' =>
                (float) ($data['zwg_basic_pay'] ?? 0),

            'zwg_employee_rate' =>
                $this->normalisePercentageRate(
                    $data['zwg_employee_rate'] ?? 0
                ),

            'zwg_employer_rate' =>
                $this->normalisePercentageRate(
                    $data['zwg_employer_rate'] ?? 0
                ),

            'zwg_employee_contribution' =>
                (float) ($data['zwg_employee_contribution'] ?? 0),

            'zwg_employer_contribution' =>
                (float) ($data['zwg_employer_contribution'] ?? 0),

            'zwg_employee_avc' =>
                (float) ($data['zwg_employee_avc'] ?? 0),

            'zwg_employer_avc' =>
                (float) ($data['zwg_employer_avc'] ?? 0),

            'zwg_employee_arrear' =>
                (float) ($data['zwg_employee_arrear'] ?? 0),

            'zwg_employer_arrear' =>
                (float) ($data['zwg_employer_arrear'] ?? 0),

            'zwg_employee_transfer_in' =>
                (float) ($data['zwg_employee_transfer_in'] ?? 0),

            'zwg_employer_transfer_in' =>
                (float) ($data['zwg_employer_transfer_in'] ?? 0),

            'zwg_employee_late_interest' =>
                (float) ($data['zwg_employee_late_interest'] ?? 0),

            'zwg_employer_late_interest' =>
                (float) ($data['zwg_employer_late_interest'] ?? 0),
        ];

        $basicPay =
            (float) ($data['basic_pay'] ?? 0);

        $employeeRate =
            $this->normalisePercentageRate(
                $data['employee_rate'] ?? 0
            );

        $employerRate =
            $this->normalisePercentageRate(
                $data['employer_rate'] ?? 0
            );

        $employeeContribution =
            (float) ($data['employee_contribution'] ?? 0);

        $employerContribution =
            (float) ($data['employer_contribution'] ?? 0);

        $employeeAvc =
            (float) ($data['employee_avc'] ?? 0);

        $employerAvc =
            (float) ($data['employer_avc'] ?? 0);

        $employeeArrear =
            (float) ($data['employee_arrear'] ?? 0);

        $employerArrear =
            (float) ($data['employer_arrear'] ?? 0);

        $employeeTransferIn =
            (float) ($data['employee_transfer_in'] ?? 0);

        $employerTransferIn =
            (float) ($data['employer_transfer_in'] ?? 0);

        $employeeLateInterest =
            (float) ($data['employee_late_interest'] ?? 0);

        $employerLateInterest =
            (float) ($data['employer_late_interest'] ?? 0);

        if ($currency === 'ZWG') {
            $mapped['zwg_basic_pay'] = $basicPay;
            $mapped['zwg_employee_rate'] = $employeeRate;
            $mapped['zwg_employer_rate'] = $employerRate;
            $mapped['zwg_employee_contribution'] = $employeeContribution;
            $mapped['zwg_employer_contribution'] = $employerContribution;
            $mapped['zwg_employee_avc'] = $employeeAvc;
            $mapped['zwg_employer_avc'] = $employerAvc;
            $mapped['zwg_employee_arrear'] = $employeeArrear;
            $mapped['zwg_employer_arrear'] = $employerArrear;
            $mapped['zwg_employee_transfer_in'] = $employeeTransferIn;
            $mapped['zwg_employer_transfer_in'] = $employerTransferIn;
            $mapped['zwg_employee_late_interest'] = $employeeLateInterest;
            $mapped['zwg_employer_late_interest'] = $employerLateInterest;
        }

        if ($currency === 'USD') {
            $mapped['usd_basic_pay'] = $basicPay;
            $mapped['usd_employee_rate'] = $employeeRate;
            $mapped['usd_employer_rate'] = $employerRate;
            $mapped['usd_employee_contribution'] = $employeeContribution;
            $mapped['usd_employer_contribution'] = $employerContribution;
            $mapped['usd_employee_avc'] = $employeeAvc;
            $mapped['usd_employer_avc'] = $employerAvc;
            $mapped['usd_employee_arrear'] = $employeeArrear;
            $mapped['usd_employer_arrear'] = $employerArrear;
            $mapped['usd_employee_transfer_in'] = $employeeTransferIn;
            $mapped['usd_employer_transfer_in'] = $employerTransferIn;
            $mapped['usd_employee_late_interest'] = $employeeLateInterest;
            $mapped['usd_employer_late_interest'] = $employerLateInterest;
        }

        $mapped['currency_code'] =
            $currency;

        return $mapped;
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
        if (blank($data['surname'] ?? null)) {
            $errors[] =
                'Surname is missing.';
        }

        if (blank($data['first_names'] ?? null)) {
            $errors[] =
                'First name is missing.';
        }

        if (
            blank($data['pension_reference_number'] ?? null)
            &&
            blank($data['penad_member_number'] ?? null)
            &&
            blank($data['penerp_member_number'] ?? null)
            &&
            blank($data['staff_number'] ?? null)
            &&
            blank($data['national_id'] ?? null)
        ) {
            $errors[] =
                'No PenAd/Pension reference number, PENERP member number, staff number or National ID was supplied.';
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
        if (blank($data['employer_number'] ?? null)) {
            $errors[] =
                'Employer Number is missing from the contribution row.';

            return;
        }

        $excelEmployer = strtoupper(
            trim(
                (string) $data['employer_number']
            )
        );

        $validEmployerNumbers = collect([
            $batch->employer->employer_number ?? null,
            $batch->employer->penad_employer_number ?? null,
            $batch->employer->fundworx_employer_number ?? null,
        ])
            ->filter(
                fn ($value) =>
                    filled($value)
            )
            ->map(
                fn ($value) =>
                    strtoupper(
                        trim(
                            (string) $value
                        )
                    )
            );

        if (
            $validEmployerNumbers->isNotEmpty()
            &&
            !$validEmployerNumbers->contains(
                $excelEmployer
            )
        ) {
            $errors[] =
                'The employer number '
                . $excelEmployer
                . ' in the Excel row does not match the employer selected for this upload.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Contribution Period
    |--------------------------------------------------------------------------
    */

    private function validatePeriod(
        ContributionImportBatch $batch,
        array $data,
        array &$warnings
    ): void {
        if (blank($data['due_date'] ?? null)) {
            $warnings[] =
                'The contribution Due Date is missing.';

            return;
        }

        try {
            $rowDate = Carbon::parse(
                $data['due_date']
            );

            $periodYear =
                (int) $batch->contributionPeriod->period_year;

            $periodMonth =
                (int) $batch->contributionPeriod->period_month;

            if (
                $rowDate->year !== $periodYear
                ||
                $rowDate->month !== $periodMonth
            ) {
                $selectedPeriod = Carbon::create(
                    $periodYear,
                    $periodMonth,
                    1
                );

                $warnings[] =
                    'CONTRIBUTION PERIOD EXCEPTION: '
                    . 'The Excel due date is '
                    . $rowDate->format('d M Y')
                    . ', but the selected contribution period is '
                    . $selectedPeriod->format('F Y')
                    . '. Please confirm that the correct contribution period was selected.';
            }
        } catch (Throwable) {
            $warnings[] =
                'The due date supplied in this row could not be interpreted.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Validation
    |--------------------------------------------------------------------------
    */

    private function validateFinancialValues(
        string $currency,
        array $data,
        bool $isNewMember,
        array &$warnings
    ): void {
        $prefix = strtolower($currency);

        $basicPay =
            (float) ($data[$prefix . '_basic_pay'] ?? 0);

        $employeeRate =
            $this->normalisePercentageRate(
                $data[$prefix . '_employee_rate'] ?? 0
            );

        $employerRate =
            $this->normalisePercentageRate(
                $data[$prefix . '_employer_rate'] ?? 0
            );

        $employeeContribution =
            (float) ($data[$prefix . '_employee_contribution'] ?? 0);

        $employerContribution =
            (float) ($data[$prefix . '_employer_contribution'] ?? 0);

        $employeeAvc =
            (float) ($data[$prefix . '_employee_avc'] ?? 0);

        $employerAvc =
            (float) ($data[$prefix . '_employer_avc'] ?? 0);

        $employeeArrear =
            (float) ($data[$prefix . '_employee_arrear'] ?? 0);

        $employerArrear =
            (float) ($data[$prefix . '_employer_arrear'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Negative Contributions
        |--------------------------------------------------------------------------
        */

        if (
            collect([
                $employeeContribution,
                $employerContribution,
                $employeeAvc,
                $employerAvc,
                $employeeArrear,
                $employerArrear,
            ])->contains(
                fn ($amount) =>
                    (float) $amount < 0
            )
        ) {
            $warnings[] =
                'CONTRIBUTION EXCEPTION: Negative '
                . $currency
                . ' contribution detected. '
                . 'This row requires review as a possible adjustment/correction.';
        }

        /*
        |--------------------------------------------------------------------------
        | Basic Pay
        |--------------------------------------------------------------------------
        */

        if ($basicPay <= 0) {
            if (
                $employeeContribution != 0
                ||
                $employerContribution != 0
            ) {
                $warnings[] =
                    'CONTRIBUTION EXCEPTION: '
                    . $currency
                    . ' contribution exists but pensionable Basic Pay is zero or negative.';
            }

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Rate = 17.30%
        |--------------------------------------------------------------------------
        */

        $requiredEmployerRate = 17.30;

        if (
            abs(
                $employerRate
                -
                $requiredEmployerRate
            ) > 0.001
        ) {
            $warnings[] =
                'RATE EXCEPTION: '
                . $currency
                . ' Employer Rate is '
                . number_format($employerRate, 2)
                . '%. Expected employer rate is 17.30%.';
        }

        /*
        |--------------------------------------------------------------------------
        | Employer Contribution
        |--------------------------------------------------------------------------
        */

        $expectedEmployerContribution = round(
            $basicPay
            *
            ($requiredEmployerRate / 100),
            2
        );

        if (
            abs(
                $employerContribution
                -
                $expectedEmployerContribution
            ) > 0.01
        ) {
            $variance =
                $expectedEmployerContribution
                -
                $employerContribution;

            $warnings[] =
                'CONTRIBUTION EXCEPTION: '
                . $currency
                . ' Employer Contribution does not agree with 17.30% of Basic Pay. '
                . 'Basic Pay: '
                . number_format($basicPay, 2)
                . ', System calculated: '
                . number_format($expectedEmployerContribution, 2)
                . ', Schedule amount: '
                . number_format($employerContribution, 2)
                . ', Variance: '
                . number_format($variance, 2)
                . '.';
        }

        /*
        |--------------------------------------------------------------------------
        | Employee Rate
        |--------------------------------------------------------------------------
        */

        if ($isNewMember) {
            $calculationEmployeeRate = 6.00;

            if (
                abs(
                    $employeeRate - 6.00
                ) > 0.001
            ) {
                $warnings[] =
                    'RATE EXCEPTION: '
                    . $currency
                    . ' Employee Rate for a proposed new member is '
                    . number_format($employeeRate, 2)
                    . '%. New members must contribute at 6.00%.';
            }
        } else {
            /*
            |--------------------------------------------------------------------------
            | Existing Member = 4.00% to 6.00%
            |--------------------------------------------------------------------------
            */

            if (
                $employeeRate < 4.00
                ||
                $employeeRate > 6.00
            ) {
                $warnings[] =
                    'RATE EXCEPTION: '
                    . $currency
                    . ' Employee Rate is '
                    . number_format($employeeRate, 2)
                    . '%. Existing members are expected to have an employee contribution rate between 4.00% and 6.00%.';
            }

            $calculationEmployeeRate =
                $employeeRate;
        }

        /*
        |--------------------------------------------------------------------------
        | Employee Contribution
        |--------------------------------------------------------------------------
        */

        $expectedEmployeeContribution = round(
            $basicPay
            *
            ($calculationEmployeeRate / 100),
            2
        );

        if (
            abs(
                $employeeContribution
                -
                $expectedEmployeeContribution
            ) > 0.01
        ) {
            $variance =
                $expectedEmployeeContribution
                -
                $employeeContribution;

            $warnings[] =
                'CONTRIBUTION EXCEPTION: '
                . $currency
                . ' Employee Contribution does not agree with '
                . number_format($calculationEmployeeRate, 2)
                . '% of Basic Pay. '
                . 'Basic Pay: '
                . number_format($basicPay, 2)
                . ', System calculated: '
                . number_format($expectedEmployeeContribution, 2)
                . ', Schedule amount: '
                . number_format($employeeContribution, 2)
                . ', Variance: '
                . number_format($variance, 2)
                . '.';
        }

        /*
        |--------------------------------------------------------------------------
        | Zero Contributions
        |--------------------------------------------------------------------------
        */

        if (
            $employeeContribution == 0
            &&
            $employerContribution == 0
            &&
            $employeeAvc == 0
            &&
            $employerAvc == 0
            &&
            $employeeArrear == 0
            &&
            $employerArrear == 0
        ) {
            $warnings[] =
                'All '
                . $currency
                . ' contribution amounts are zero.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Percentage Normalisation
    |--------------------------------------------------------------------------
    */

    private function normalisePercentageRate(
        mixed $value
    ): float {
        $rate = (float) ($value ?? 0);

        if (
            $rate > 0
            &&
            $rate <= 1
        ) {
            return round(
                $rate * 100,
                6
            );
        }

        return $rate;
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

        if (!$currentEmployment) {
            $warnings[] =
                'The matched member does not currently have an active employer relationship in PENERP.';

            return;
        }

        if (
            (int) $currentEmployment->employer_id
            !==
            (int) $batch->employer_id
        ) {
            $warnings[] =
                'The matched member currently belongs to another employer in PENERP.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Member Identity
    |--------------------------------------------------------------------------
    */

    private function validateExistingMemberIdentity(
        Member $member,
        array $data,
        array &$warnings
    ): void {
        $excelNationalId =
            Member::normalizeNationalId(
                $data['national_id'] ?? null
            );

        if (
            $excelNationalId
            &&
            $member->national_id_normalized
            &&
            $excelNationalId !==
            $member->national_id_normalized
        ) {
            $warnings[] =
                'The National ID on the contribution schedule differs from the National ID stored against the matched member.';
        }

        if (
            filled($data['surname'] ?? null)
            &&
            strtoupper(
                trim(
                    (string) $data['surname']
                )
            )
            !==
            strtoupper(
                trim(
                    (string) $member->surname
                )
            )
        ) {
            $warnings[] =
                'The surname on the contribution schedule differs from the surname stored against the matched member.';
        }

        if (
            filled($data['first_names'] ?? null)
            &&
            filled($member->first_names)
            &&
            strtoupper(
                trim(
                    (string) $data['first_names']
                )
            )
            !==
            strtoupper(
                trim(
                    (string) $member->first_names
                )
            )
        ) {
            $warnings[] =
                'The first name(s) on the contribution schedule differ from the name(s) stored against the matched member.';
        }

        if (
            filled($data['date_of_birth'] ?? null)
            &&
            filled($member->date_of_birth)
        ) {
            try {
                $excelDob =
                    Carbon::parse(
                        $data['date_of_birth']
                    )
                        ->toDateString();

                $memberDob =
                    Carbon::parse(
                        $member->date_of_birth
                    )
                        ->toDateString();

                if ($excelDob !== $memberDob) {
                    $warnings[] =
                        'The Date of Birth on the contribution schedule differs from the Date of Birth stored against the matched member.';
                }
            } catch (Throwable) {
                $warnings[] =
                    'The Date of Birth on the contribution schedule could not be compared with the existing member record.';
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Proposed New Member
    |--------------------------------------------------------------------------
    */

    private function validateNewMemberCandidate(
        ContributionImportBatch $batch,
        array $data,
        array &$errors,
        array &$warnings
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Staff Number
        |--------------------------------------------------------------------------
        */

        $staffNumber = trim(
            (string) (
                $data['staff_number']
                ?? ''
            )
        );

        if ($staffNumber === '') {
            $errors[] =
                'Staff Number is required for a proposed new member.';
        } else {
            $staffNumberAlreadyExists =
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

            if ($staffNumberAlreadyExists) {
                $errors[] =
                    'Staff number '
                    . $staffNumber
                    . ' is already assigned to a current member under this employer.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Mandatory New Member Fields
        |--------------------------------------------------------------------------
        |
        | IMPORTANT:
        |
        | Phone number, email address and home address are deliberately NOT
        | mandatory.
        |
        */

        $requiredNewMemberFields = [
            'date_joined_fund' =>
                'Date Joined Fund',

            'date_joined_employer' =>
                'Date Joined Employer',

            'date_of_birth' =>
                'Date of Birth',

            'gender' =>
                'Gender',

            'national_id' =>
                'National ID',

            'marital_status' =>
                'Marital Status',
        ];

        foreach (
            $requiredNewMemberFields
            as $field => $label
        ) {
            if (
                blank(
                    $data[$field]
                    ?? null
                )
            ) {
                $errors[] =
                    $label
                    . ' is required for a proposed new member.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | New Member Age
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data['date_of_birth']
                ?? null
            )
        ) {
            try {
                $dateOfBirth =
                    Carbon::parse(
                        $data['date_of_birth']
                    );

                $ageDate =
                    filled(
                        $data['due_date']
                        ?? null
                    )
                        ? Carbon::parse(
                            $data['due_date']
                        )
                        : Carbon::parse(
                            $batch
                                ->contributionPeriod
                                ->period_date
                        );

                if (
                    $dateOfBirth->gt(
                        $ageDate
                    )
                ) {
                    $errors[] =
                        'Date of Birth for the proposed new member cannot be after the contribution period date.';
                } else {
                    $age =
                        $dateOfBirth
                            ->diffInYears(
                                $ageDate
                            );

                    if ($age >= 60) {
                        $errors[] =
                            'The proposed new member is '
                            . $age
                            . ' years old at '
                            . $ageDate->format('d M Y')
                            . '. A new member aged 60 years or above cannot contribute or be created through the monthly contribution upload.';
                    }
                }
            } catch (Throwable) {
                $errors[] =
                    'Date of Birth for the proposed new member could not be interpreted.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Date Joined Fund
        |--------------------------------------------------------------------------
        */

        if (
            filled($data['date_joined_fund'] ?? null)
            &&
            filled($data['date_of_birth'] ?? null)
        ) {
            try {
                $dateJoinedFund =
                    Carbon::parse(
                        $data['date_joined_fund']
                    );

                $dateOfBirth =
                    Carbon::parse(
                        $data['date_of_birth']
                    );

                if (
                    $dateJoinedFund->lt(
                        $dateOfBirth
                    )
                ) {
                    $errors[] =
                        'Date Joined Fund cannot be before Date of Birth.';
                }
            } catch (Throwable) {
                $errors[] =
                    'Date Joined Fund could not be interpreted.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Date Joined Employer
        |--------------------------------------------------------------------------
        */

        if (
            filled(
                $data['date_joined_employer']
                ?? null
            )
            &&
            filled(
                $data['date_of_birth']
                ?? null
            )
        ) {
            try {
                $dateJoinedEmployer =
                    Carbon::parse(
                        $data['date_joined_employer']
                    );

                $dateOfBirth =
                    Carbon::parse(
                        $data['date_of_birth']
                    );

                if (
                    $dateJoinedEmployer->lt(
                        $dateOfBirth
                    )
                ) {
                    $errors[] =
                        'Date Joined Employer cannot be before Date of Birth.';
                }
            } catch (Throwable) {
                $errors[] =
                    'Date Joined Employer could not be interpreted.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | National ID Duplicate
        |--------------------------------------------------------------------------
        */

        $normalizedNationalId =
            Member::normalizeNationalId(
                $data['national_id']
                ?? null
            );

        if ($normalizedNationalId) {
            $nationalIdExists =
                Member::query()
                    ->where(
                        'national_id_normalized',
                        $normalizedNationalId
                    )
                    ->exists();

            if ($nationalIdExists) {
                $errors[] =
                    'National ID '
                    . (
                        $data['national_id']
                        ?? ''
                    )
                    . ' already belongs to an existing PENERP member.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Optional Email
        |--------------------------------------------------------------------------
        |
        | Blank email = allowed.
        | Supplied but invalid email = error.
        |
        */

        if (
            filled(
                $data['email_address']
                ?? null
            )
            &&
            !filter_var(
                trim(
                    (string)
                    $data['email_address']
                ),
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $errors[] =
                'The Email Address supplied for the proposed new member is invalid.';
        }

        /*
        |--------------------------------------------------------------------------
        | Phone Number
        |--------------------------------------------------------------------------
        |
        | Optional. No validation error is raised when missing.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Home Address
        |--------------------------------------------------------------------------
        |
        | Optional. No validation error is raised when missing.
        |
        */

        /*
        |--------------------------------------------------------------------------
        | Generated Member Number
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data['penerp_member_number']
                ?? null
            )
            &&
            blank(
                $data['penad_member_number']
                ?? null
            )
            &&
            blank(
                $data['pension_reference_number']
                ?? null
            )
        ) {
            $warnings[] =
                'No existing PENERP/PenAd member number was supplied. '
                . 'A new member number will be allocated automatically when this batch is posted.';
        }

        $warnings[] =
            'No existing member matched this row. '
            . 'It has been classified as a proposed new member and has not yet been permanently created.';
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
        $memberIds =
            MemberEmployment::query()
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'is_current',
                    true
                )
                ->pluck(
                    'member_id'
                )
                ->unique();

        if (!empty($scheduledMemberIds)) {
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

        foreach ($members as $member) {
            ContributionPeriodMemberStatus::updateOrCreate(
                [
                    'contribution_period_id' =>
                        $batch->contribution_period_id,

                    'member_id' =>
                        $member->id,
                ],
                [
                    'employer_id' =>
                        $batch->employer_id,

                    'contribution_status' =>
                        'nil_contributor',

                    'reason' =>
                        'Active member did not appear on the expected contribution schedule for this contribution period.',

                    'import_batch_id' =>
                        $batch->id,
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
                                $data['pension_reference_number']
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data['penerp_member_number']
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        trim(
                            (string) (
                                $data['staff_number']
                                ?? ''
                            )
                        )
                    ),

                    strtoupper(
                        Member::normalizeNationalId(
                            $data['national_id']
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
    | Batch Totals
    |--------------------------------------------------------------------------
    */

    private function addTotals(
        array &$totals,
        array $data
    ): void {
        $totals['usd_basic_pay_total'] +=
            (float) ($data['usd_basic_pay'] ?? 0);

        $totals['usd_employee_contribution_total'] +=
            (float) ($data['usd_employee_contribution'] ?? 0);

        $totals['usd_employer_contribution_total'] +=
            (float) ($data['usd_employer_contribution'] ?? 0);

        $totals['usd_employee_avc_total'] +=
            (float) ($data['usd_employee_avc'] ?? 0);

        $totals['usd_employer_avc_total'] +=
            (float) ($data['usd_employer_avc'] ?? 0);

        $totals['zwg_basic_pay_total'] +=
            (float) ($data['zwg_basic_pay'] ?? 0);

        $totals['zwg_employee_contribution_total'] +=
            (float) ($data['zwg_employee_contribution'] ?? 0);

        $totals['zwg_employer_contribution_total'] +=
            (float) ($data['zwg_employer_contribution'] ?? 0);

        $totals['zwg_employee_avc_total'] +=
            (float) ($data['zwg_employee_avc'] ?? 0);

        $totals['zwg_employer_avc_total'] +=
            (float) ($data['zwg_employer_avc'] ?? 0);
    }
}