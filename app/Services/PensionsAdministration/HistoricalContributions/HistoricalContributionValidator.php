<?php

namespace App\Services\PensionsAdministration\HistoricalContributions;

use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportBatch;
use App\Models\PensionsAdministration\HistoricalContributions\HistoricalContributionImportRow;
use App\Services\PensionsAdministration\HistoricalContributions\HistoricalMembershipStatus;
use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class HistoricalContributionValidator
{
    /*
    |--------------------------------------------------------------------------
    | SQL Server Safe Insert Chunk
    |--------------------------------------------------------------------------
    |
    | historical_contribution_import_rows is a very wide table.
    |
    | Do NOT increase this to 200/500 using Laravel's normal insert().
    | SQL Server has a parameter limit and this table contains many columns.
    |
    */

    private const INSERT_CHUNK = 20;

    /*
    |--------------------------------------------------------------------------
    | Progress
    |--------------------------------------------------------------------------
    */

    private const PROGRESS_UPDATES = 50;

    public function __construct(
        private readonly HistoricalContributionExcelReader $excelReader,
        private readonly HistoricalContributionMemberMatcher $memberMatcher
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Process
    |--------------------------------------------------------------------------
    */

    public function process(
        HistoricalContributionImportBatch $batch
    ): void {
        $batch->update([
            'status' => 'processing',
            'progress_percentage' => 1,

            'total_source_rows' => 0,
            'processed_source_rows' => 0,

            'total_transaction_rows' => 0,
            'processed_transaction_rows' => 0,

            'valid_transaction_rows' => 0,
            'warning_transaction_rows' => 0,
            'error_transaction_rows' => 0,
            'duplicate_transaction_rows' => 0,

            'matched_member_rows' => 0,
            'new_member_rows' => 0,
            'ambiguous_member_rows' => 0,

            'new_members_detected' => 0,
            'new_members_created' => 0,

            'contributed_periods' => 0,
            'zero_contribution_periods' => 0,
            'break_in_service_periods' => 0,

            'posted_transaction_rows' => 0,
            'posted_service_period_rows' => 0,

            'failure_reason' => null,

            'processing_started_at' => now(),
            'validation_completed_at' => null,
            'completed_at' => null,
        ]);

        try {
            /*
            |--------------------------------------------------------------------------
            | File
            |--------------------------------------------------------------------------
            */

            $disk =
                Storage::disk(
                    'local'
                );

            if (
                !$disk->exists(
                    $batch->file_path
                )
            ) {
                throw new RuntimeException(
                    'Historical contribution Excel file could not be found: '
                    . $batch->file_path
                );
            }

            $path =
                $disk->path(
                    $batch->file_path
                );

            /*
            |--------------------------------------------------------------------------
            | Inspect Once
            |--------------------------------------------------------------------------
            */

            $inspection =
                $this->excelReader->inspect(
                    $path
                );

            $estimatedSourceRows =
                (int) (
                    $inspection[
                        'estimated_source_rows'
                    ]
                    ??
                    0
                );

            if (
                $estimatedSourceRows
                <=
                0
            ) {
                throw new RuntimeException(
                    'The historical contribution workbook does not contain any member rows.'
                );
            }

            $batch->update([
                'total_source_rows' =>
                    $estimatedSourceRows,

                'progress_percentage' =>
                    4,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Remove Previous Staging
            |--------------------------------------------------------------------------
            */

            DB::table(
                'historical_contribution_import_rows'
            )
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->delete();

            /*
            |--------------------------------------------------------------------------
            | Initialise Lookup Cache Once
            |--------------------------------------------------------------------------
            */

            $this->memberMatcher->initialise();

            $batch->update([
                'progress_percentage' =>
                    7,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Counters
            |--------------------------------------------------------------------------
            */

            $processedSourceRows = 0;
            $processedTransactions = 0;

            $validTransactions = 0;
            $warningTransactions = 0;
            $errorTransactions = 0;
            $duplicateTransactions = 0;

            $matchedMemberRows = 0;
            $newMemberRows = 0;
            $ambiguousMemberRows = 0;

            $contributedPeriods = 0;
            $zeroContributionPeriods = 0;
            $breakInServicePeriods = 0;

            /*
            |--------------------------------------------------------------------------
            | Fast In-Memory Sets
            |--------------------------------------------------------------------------
            */

            $newMemberKeys = [];

            $seenDuplicateKeys = [];

            /*
            |--------------------------------------------------------------------------
            | Existing Contribution Cache
            |--------------------------------------------------------------------------
            |
            | One SQL query maximum per member/employer combination.
            |
            */

            $existingContributionCache = [];

            /*
            |--------------------------------------------------------------------------
            | Insert Buffer
            |--------------------------------------------------------------------------
            */

            $insertBuffer = [];

            /*
            |--------------------------------------------------------------------------
            | Progress Interval
            |--------------------------------------------------------------------------
            */

            $progressInterval =
                max(
                    1,
                    (int) ceil(
                        $estimatedSourceRows
                        /
                        self::PROGRESS_UPDATES
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Read Source
            |--------------------------------------------------------------------------
            */

            foreach (
                $this->excelReader->rows(
                    $path,
                    $inspection
                )
                as $sourceRow
            ) {
                $processedSourceRows++;

                $sourceRowNumber =
                    (int) (
                        $sourceRow[
                            'source_row_number'
                        ]
                        ??
                        0
                    );

                $memberData =
                    $sourceRow[
                        'member_data'
                    ]
                    ??
                    [];

                /*
                |--------------------------------------------------------------------------
                | Preserve Historical Membership Status
                |--------------------------------------------------------------------------
                |
                | Never collapse Exited / Suspended / Deferred / Waiting Approval into
                | inactive.  The raw value is retained for audit/review and the canonical
                | value is carried into every staging transaction.
                |
                */

                $rawMembershipStatus =
                    $memberData[
                        'membership_status_raw'
                    ]
                    ??
                    $memberData[
                        'membership_status'
                    ]
                    ??
                    null;

                $memberData[
                    'membership_status_raw'
                ] =
                    $rawMembershipStatus;

                $memberData[
                    'membership_status'
                ] =
                    HistoricalMembershipStatus::normalize(
                        $rawMembershipStatus
                    );

                /*
                |--------------------------------------------------------------------------
                | Match In Memory
                |--------------------------------------------------------------------------
                */

                $match =
                    $this->memberMatcher->match(
                        $memberData
                    );

                $employer =
                    $match[
                        'employer'
                    ]
                    ??
                    null;

                $member =
                    $match[
                        'member'
                    ]
                    ??
                    null;

                $isNewMember =
                    (bool) (
                        $match[
                            'is_new_member'
                        ]
                        ??
                        false
                    );

                $ambiguous =
                    (bool) (
                        $match[
                            'ambiguous'
                        ]
                        ??
                        false
                    );

                if ($member) {
                    $matchedMemberRows++;
                } elseif ($isNewMember) {
                    $newMemberRows++;
                } elseif ($ambiguous) {
                    $ambiguousMemberRows++;
                }

                /*
                |--------------------------------------------------------------------------
                | Proposed Member Key
                |--------------------------------------------------------------------------
                */

                $newMemberKey =
                    null;

                if (
                    $isNewMember
                    &&
                    $employer
                ) {
                    $newMemberKey =
                        $this->makeNewMemberIdentityKey(
                            employerId:
                                (int) $employer->id,

                            data:
                                $memberData
                        );

                    $newMemberKeys[
                        $newMemberKey
                    ] =
                        true;
                }

                /*
                |--------------------------------------------------------------------------
                | Periods
                |--------------------------------------------------------------------------
                */

                $periods =
                    $sourceRow[
                        'periods'
                    ]
                    ??
                    [];

                /*
                |--------------------------------------------------------------------------
                | Determine Actual Activity Window
                |--------------------------------------------------------------------------
                */

                $serviceWindow =
                    $this->determineServiceWindow(
                        $periods
                    );

                /*
                |--------------------------------------------------------------------------
                | Take-On
                |--------------------------------------------------------------------------
                */

                $takeOn =
                    $sourceRow[
                        'take_on'
                    ]
                    ??
                    null;

                if ($takeOn) {
                    if (
                        (
                            $takeOn[
                                'service_status'
                            ]
                            ??
                            null
                        )
                        ===
                        'blank'
                    ) {
                        $takeOn[
                            'service_status'
                        ] =
                            'outside_service';
                    }

                    $this->processTransaction(
                        batch:
                            $batch,

                        sourceRowNumber:
                            $sourceRowNumber,

                        memberData:
                            $memberData,

                        transaction:
                            $takeOn,

                        match:
                            $match,

                        member:
                            $member,

                        employer:
                            $employer,

                        isNewMember:
                            $isNewMember,

                        newMemberKey:
                            $newMemberKey,

                        seenDuplicateKeys:
                            $seenDuplicateKeys,

                        existingContributionCache:
                            $existingContributionCache,

                        insertBuffer:
                            $insertBuffer,

                        processedTransactions:
                            $processedTransactions,

                        validTransactions:
                            $validTransactions,

                        warningTransactions:
                            $warningTransactions,

                        errorTransactions:
                            $errorTransactions,

                        duplicateTransactions:
                            $duplicateTransactions,

                        contributedPeriods:
                            $contributedPeriods,

                        zeroContributionPeriods:
                            $zeroContributionPeriods,

                        breakInServicePeriods:
                            $breakInServicePeriods
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Historical Months
                |--------------------------------------------------------------------------
                */

                foreach (
                    $periods
                    as $transaction
                ) {
                    $serviceStatus =
                        $transaction[
                            'service_status'
                        ]
                        ??
                        'blank';

                    /*
                    |--------------------------------------------------------------------------
                    | Skip Meaningless Blank Months
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $serviceStatus
                        ===
                        'blank'
                    ) {
                        if (
                            $this->periodFallsInsideServiceWindow(
                                transaction:
                                    $transaction,

                                serviceWindow:
                                    $serviceWindow
                            )
                        ) {
                            $transaction[
                                'service_status'
                            ] =
                                'break_in_service';
                        } else {
                            continue;
                        }
                    }

                    $this->processTransaction(
                        batch:
                            $batch,

                        sourceRowNumber:
                            $sourceRowNumber,

                        memberData:
                            $memberData,

                        transaction:
                            $transaction,

                        match:
                            $match,

                        member:
                            $member,

                        employer:
                            $employer,

                        isNewMember:
                            $isNewMember,

                        newMemberKey:
                            $newMemberKey,

                        seenDuplicateKeys:
                            $seenDuplicateKeys,

                        existingContributionCache:
                            $existingContributionCache,

                        insertBuffer:
                            $insertBuffer,

                        processedTransactions:
                            $processedTransactions,

                        validTransactions:
                            $validTransactions,

                        warningTransactions:
                            $warningTransactions,

                        errorTransactions:
                            $errorTransactions,

                        duplicateTransactions:
                            $duplicateTransactions,

                        contributedPeriods:
                            $contributedPeriods,

                        zeroContributionPeriods:
                            $zeroContributionPeriods,

                        breakInServicePeriods:
                            $breakInServicePeriods
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Flush
                |--------------------------------------------------------------------------
                */

                if (
                    count(
                        $insertBuffer
                    )
                    >=
                    self::INSERT_CHUNK
                ) {
                    $this->flushInsertBuffer(
                        $insertBuffer
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Progress Update
                |--------------------------------------------------------------------------
                |
                | Approximately every 2%.
                |
                */

                if (
                    $processedSourceRows
                    ===
                    1
                    ||
                    $processedSourceRows
                    >=
                    $estimatedSourceRows
                    ||
                    (
                        $processedSourceRows
                        %
                        $progressInterval
                    )
                    ===
                    0
                ) {
                    $progress =
                        7
                        +
                        (
                            (
                                $processedSourceRows
                                /
                                max(
                                    1,
                                    $estimatedSourceRows
                                )
                            )
                            *
                            88
                        );

                    $batch->update([
                        'processed_source_rows' =>
                            $processedSourceRows,

                        'total_transaction_rows' =>
                            $processedTransactions,

                        'processed_transaction_rows' =>
                            $processedTransactions,

                        'valid_transaction_rows' =>
                            $validTransactions,

                        'warning_transaction_rows' =>
                            $warningTransactions,

                        'error_transaction_rows' =>
                            $errorTransactions,

                        'duplicate_transaction_rows' =>
                            $duplicateTransactions,

                        'matched_member_rows' =>
                            $matchedMemberRows,

                        'new_member_rows' =>
                            $newMemberRows,

                        'ambiguous_member_rows' =>
                            $ambiguousMemberRows,

                        'new_members_detected' =>
                            count(
                                $newMemberKeys
                            ),

                        'contributed_periods' =>
                            $contributedPeriods,

                        'zero_contribution_periods' =>
                            $zeroContributionPeriods,

                        'break_in_service_periods' =>
                            $breakInServicePeriods,

                        'progress_percentage' =>
                            round(
                                min(
                                    95,
                                    $progress
                                ),
                                2
                            ),
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Free Per-Row References
                |--------------------------------------------------------------------------
                */

                unset(
                    $sourceRow,
                    $memberData,
                    $match,
                    $member,
                    $employer,
                    $periods,
                    $takeOn
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Final Flush
            |--------------------------------------------------------------------------
            */

            $this->flushInsertBuffer(
                $insertBuffer
            );

            /*
            |--------------------------------------------------------------------------
            | Complete
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'processed_source_rows' =>
                    $processedSourceRows,

                'total_source_rows' =>
                    $processedSourceRows,

                'total_transaction_rows' =>
                    $processedTransactions,

                'processed_transaction_rows' =>
                    $processedTransactions,

                'valid_transaction_rows' =>
                    $validTransactions,

                'warning_transaction_rows' =>
                    $warningTransactions,

                'error_transaction_rows' =>
                    $errorTransactions,

                'duplicate_transaction_rows' =>
                    $duplicateTransactions,

                'matched_member_rows' =>
                    $matchedMemberRows,

                'new_member_rows' =>
                    $newMemberRows,

                'ambiguous_member_rows' =>
                    $ambiguousMemberRows,

                'new_members_detected' =>
                    count(
                        $newMemberKeys
                    ),

                'contributed_periods' =>
                    $contributedPeriods,

                'zero_contribution_periods' =>
                    $zeroContributionPeriods,

                'break_in_service_periods' =>
                    $breakInServicePeriods,

                'status' =>
                    'awaiting_review',

                'progress_percentage' =>
                    100,

                'validation_completed_at' =>
                    now(),

                'completed_at' =>
                    now(),

                'failure_reason' =>
                    null,
            ]);

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
    | Process Transaction
    |--------------------------------------------------------------------------
    */

    private function processTransaction(
        HistoricalContributionImportBatch $batch,
        int $sourceRowNumber,
        array $memberData,
        array $transaction,
        array $match,
        mixed $member,
        mixed $employer,
        bool $isNewMember,
        ?string $newMemberKey,
        array &$seenDuplicateKeys,
        array &$existingContributionCache,
        array &$insertBuffer,
        int &$processedTransactions,
        int &$validTransactions,
        int &$warningTransactions,
        int &$errorTransactions,
        int &$duplicateTransactions,
        int &$contributedPeriods,
        int &$zeroContributionPeriods,
        int &$breakInServicePeriods
    ): void {
        $processedTransactions++;

        $errors = [];

        $warnings = [];

        /*
        |--------------------------------------------------------------------------
        | Historical Membership Status
        |--------------------------------------------------------------------------
        */

        $rawMembershipStatus =
            $memberData[
                'membership_status_raw'
            ]
            ??
            $memberData[
                'membership_status'
            ]
            ??
            null;

        if (
            !HistoricalMembershipStatus::isRecognised(
                $rawMembershipStatus
            )
        ) {
            $warnings[] =
                'Historical membership status "'
                . (string) $rawMembershipStatus
                . '" is not recognised. The row should be reviewed before posting.';
        }

        /*
        |--------------------------------------------------------------------------
        | Matching
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $match[
                    'error'
                ]
            )
        ) {
            $errors[] =
                $match[
                    'error'
                ];
        }

        if (!$employer) {
            $errors[] =
                'Employer could not be resolved for this historical transaction.';
        }

        /*
        |--------------------------------------------------------------------------
        | New Historical Member
        |--------------------------------------------------------------------------
        */

        if ($isNewMember) {
            $this->validateHistoricalNewMember(
                data:
                    $memberData,

                errors:
                    $errors,

                warnings:
                    $warnings
            );

            if (
                (
                    $match[
                        'member_match_type'
                    ]
                    ??
                    null
                )
                ===
                'staff_number_reused_historically'
            ) {
                $warnings[] =
                    'Staff Number was previously used under this employer. This is allowed for historical data because previous holders may have exited.';
            }

            $warnings[] =
                'No existing PENERP member matched this historical contributor. A historical member will be created during posting if approved.';
        }

        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $year =
            (int) (
                $transaction[
                    'period_year'
                ]
                ??
                0
            );

        $month =
            (int) (
                $transaction[
                    'period_month'
                ]
                ??
                0
            );

        if (
            $year
            <=
            0
            ||
            $month
            <
            1
            ||
            $month
            >
            12
        ) {
            $errors[] =
                'Historical contribution period is invalid.';
        }

        $transactionType =
            $this->normaliseTransactionType(
                $transaction[
                    'transaction_type'
                ]
                ??
                'expected'
            );

        /*
        |--------------------------------------------------------------------------
        | Financial Values
        |--------------------------------------------------------------------------
        */

        $basicPay =
            $this->decimal4OrNull(
                $transaction[
                    'basic_pay'
                ]
                ??
                null
            );

        $employeeRate =
            $this->decimal6OrNull(
                $transaction[
                    'employee_rate'
                ]
                ??
                null
            );

        $employerRate =
            $this->decimal6OrNull(
                $transaction[
                    'employer_rate'
                ]
                ??
                null
            );

        $employeeContribution =
            $this->decimal4OrNull(
                $transaction[
                    'employee_contribution'
                ]
                ??
                null
            );

        $employerContribution =
            $this->decimal4OrNull(
                $transaction[
                    'employer_contribution'
                ]
                ??
                null
            );

        $employeeAvc =
            $this->decimal4OrNull(
                $transaction[
                    'employee_avc'
                ]
                ??
                null
            );

        $employerAvc =
            $this->decimal4OrNull(
                $transaction[
                    'employer_avc'
                ]
                ??
                null
            );

        /*
        |--------------------------------------------------------------------------
        | Service Status
        |--------------------------------------------------------------------------
        */

        $serviceStatus =
            $transaction[
                'service_status'
            ]
            ??
            'contributed';

        switch ($serviceStatus) {
            case 'contributed':
                $contributedPeriods++;
                break;

            case 'zero_contribution':
                $zeroContributionPeriods++;
                break;

            case 'break_in_service':
                $breakInServicePeriods++;

                $warnings[] =
                    'Break in Service: historical monthly financial cells were blank between earlier and later activity.';
                break;
        }

        /*
        |--------------------------------------------------------------------------
        | Duplicate Key
        |--------------------------------------------------------------------------
        */

        $identityPart =
            $member
                ? 'member:' . $member->id
                : 'new:' . ($newMemberKey ?? 'unknown');

        $duplicateKey =
            hash(
                'sha256',
                $identityPart
                . '|employer:'
                . ($employer?->id ?? 0)
                . '|year:'
                . $year
                . '|month:'
                . $month
                . '|type:'
                . $transactionType
            );

        $duplicateStatus =
            'none';

        /*
        |--------------------------------------------------------------------------
        | Duplicate Inside Current Excel
        |--------------------------------------------------------------------------
        */

        if (
            isset(
                $seenDuplicateKeys[
                    $duplicateKey
                ]
            )
        ) {
            $duplicateStatus =
                'duplicate_in_file';

            $duplicateTransactions++;

            $warnings[] =
                'Duplicate historical contribution detected in this upload for the same member, employer, period and transaction type.';
        } else {
            $seenDuplicateKeys[
                $duplicateKey
            ] =
                true;
        }

        /*
        |--------------------------------------------------------------------------
        | Existing PENERP Contribution
        |--------------------------------------------------------------------------
        */

        if (
            $member
            &&
            $employer
            &&
            $duplicateStatus
            ===
            'none'
        ) {
            if (
                $this->historicalContributionAlreadyExists(
                    memberId:
                        (int) $member->id,

                    employerId:
                        (int) $employer->id,

                    year:
                        $year,

                    month:
                        $month,

                    transactionType:
                        $transactionType,

                    cache:
                        $existingContributionCache
                )
            ) {
                $duplicateStatus =
                    'duplicate_existing';

                $duplicateTransactions++;

                $warnings[] =
                    'This historical contribution already exists in PENERP for the same member, employer, period and transaction type.';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validation Status
        |--------------------------------------------------------------------------
        */

        $errors =
            array_values(
                array_unique(
                    $errors
                )
            );

        $warnings =
            array_values(
                array_unique(
                    $warnings
                )
            );

        if (
            !empty(
                $errors
            )
        ) {
            $validationStatus =
                'error';

            $errorTransactions++;

        } elseif (
            !empty(
                $warnings
            )
        ) {
            $validationStatus =
                'warning';

            $warningTransactions++;

        } else {
            $validationStatus =
                'valid';

            $validTransactions++;
        }

        /*
        |--------------------------------------------------------------------------
        | Staging Row
        |--------------------------------------------------------------------------
        */

        $insertBuffer[] = [
            'import_batch_id' =>
                $batch->id,

            'source_row_number' =>
                $sourceRowNumber,

            'source_column_reference' =>
                $transaction[
                    'source_reference'
                ]
                ??
                null,

            'matched_employer_id' =>
                $employer?->id,

            'penerp_employer_number' =>
                $memberData[
                    'penerp_employer_number'
                ]
                ??
                null,

            'penad_employer_number' =>
                $memberData[
                    'penad_employer_number'
                ]
                ??
                $memberData[
                    'employer_number'
                ]
                ??
                null,

            'fundworx_employer_number' =>
                $memberData[
                    'fundworx_employer_number'
                ]
                ??
                null,

            'employer_name' =>
                $memberData[
                    'employer_name'
                ]
                ??
                null,

            'employer_match_type' =>
                $match[
                    'employer_match_type'
                ]
                ??
                null,

            'matched_member_id' =>
                $member?->id,

            'created_member_id' =>
                null,

            'member_match_type' =>
                $match[
                    'member_match_type'
                ]
                ??
                null,

            'is_new_member' =>
                $isNewMember,

            'penerp_member_number' =>
                $memberData[
                    'penerp_member_number'
                ]
                ??
                null,

            'penad_member_number' =>
                $memberData[
                    'penad_member_number'
                ]
                ??
                $memberData[
                    'legacy_member_number'
                ]
                ??
                null,

            'fundworx_member_number' =>
                $memberData[
                    'fundworx_member_number'
                ]
                ??
                null,

            'staff_number' =>
                $memberData[
                    'staff_number'
                ]
                ??
                null,

            'vote_number' =>
                $memberData[
                    'vote_number'
                ]
                ??
                null,

            'title' =>
                $memberData[
                    'title'
                ]
                ??
                null,

            'surname' =>
                $memberData[
                    'surname'
                ]
                ??
                null,

            'first_names' =>
                $memberData[
                    'first_names'
                ]
                ??
                null,

            'other_names' =>
                $memberData[
                    'other_names'
                ]
                ??
                null,

            'maiden_name' =>
                $memberData[
                    'maiden_name'
                ]
                ??
                null,

            'national_id' =>
                $memberData[
                    'national_id'
                ]
                ??
                null,

            'national_id_normalized' =>
                Member::normalizeNationalId(
                    $memberData[
                        'national_id'
                    ]
                    ??
                    null
                ),

            'date_of_birth' =>
                $memberData[
                    'date_of_birth'
                ]
                ??
                null,

            'gender' =>
                $memberData[
                    'gender'
                ]
                ??
                null,

            'marital_status' =>
                $memberData[
                    'marital_status'
                ]
                ??
                null,

            'date_joined_fund' =>
                $memberData[
                    'date_joined_fund'
                ]
                ??
                null,

            'date_joined_employer' =>
                $memberData[
                    'date_joined_employer'
                ]
                ??
                null,

            'membership_status' =>
                HistoricalMembershipStatus::normalize(
                    $memberData[
                        'membership_status_raw'
                    ]
                    ??
                    $memberData[
                        'membership_status'
                    ]
                    ??
                    null
                ),

            'occupation' =>
                $memberData[
                    'occupation'
                ]
                ??
                null,

            'email' =>
                $memberData[
                    'email'
                ]
                ??
                null,

            'secondary_email' =>
                $memberData[
                    'secondary_email'
                ]
                ??
                null,

            'cell_number' =>
                $memberData[
                    'cell_number'
                ]
                ??
                null,

            'secondary_cell_number' =>
                $memberData[
                    'secondary_cell_number'
                ]
                ??
                null,

            'physical_address_1' =>
                $memberData[
                    'physical_address_1'
                ]
                ??
                null,

            'physical_address_2' =>
                $memberData[
                    'physical_address_2'
                ]
                ??
                null,

            'physical_address_3' =>
                $memberData[
                    'physical_address_3'
                ]
                ??
                null,

            'physical_suburb' =>
                $memberData[
                    'physical_suburb'
                ]
                ??
                null,

            'physical_city' =>
                $memberData[
                    'physical_city'
                ]
                ??
                null,

            'physical_country' =>
                $memberData[
                    'physical_country'
                ]
                ??
                null,

            'postal_address_1' =>
                $memberData[
                    'postal_address_1'
                ]
                ??
                null,

            'postal_address_2' =>
                $memberData[
                    'postal_address_2'
                ]
                ??
                null,

            'postal_address_3' =>
                $memberData[
                    'postal_address_3'
                ]
                ??
                null,

            'postal_city' =>
                $memberData[
                    'postal_city'
                ]
                ??
                null,

            'postal_country' =>
                $memberData[
                    'postal_country'
                ]
                ??
                null,

            'period_year' =>
                $year,

            'period_month' =>
                $month,

            'period_date' =>
                $transaction[
                    'period_date'
                ],

            'transaction_type' =>
                $transactionType,

            'service_status' =>
                $serviceStatus,

            'currency_code' =>
                $memberData[
                    'currency_code'
                ]
                ??
                null,

            /*
            |--------------------------------------------------------------------------
            | Actual Historical Financial Values
            |--------------------------------------------------------------------------
            */

            'basic_pay' =>
                $basicPay,

            'employee_rate' =>
                $employeeRate,

            'employer_rate' =>
                $employerRate,

            'employee_contribution' =>
                $employeeContribution,

            'employer_contribution' =>
                $employerContribution,

            'employee_avc' =>
                $employeeAvc,

            'employer_avc' =>
                $employerAvc,

            'employee_arrear' =>
                null,

            'employer_arrear' =>
                null,

            'employee_transfer_in' =>
                null,

            'employer_transfer_in' =>
                null,

            'employee_late_interest' =>
                null,

            'employer_late_interest' =>
                null,

            'employee_contribution_was_blank' =>
                (bool) (
                    $transaction[
                        'employee_contribution_was_blank'
                    ]
                    ??
                    false
                ),

            'employer_contribution_was_blank' =>
                (bool) (
                    $transaction[
                        'employer_contribution_was_blank'
                    ]
                    ??
                    false
                ),

            'validation_status' =>
                $validationStatus,

            'error_messages' =>
                $errors
                    ? json_encode(
                        $errors,
                        JSON_UNESCAPED_UNICODE
                    )
                    : null,

            'warning_messages' =>
                $warnings
                    ? json_encode(
                        $warnings,
                        JSON_UNESCAPED_UNICODE
                    )
                    : null,

            'duplicate_status' =>
                $duplicateStatus,

            'duplicate_key' =>
                $duplicateKey,

            'duplicate_of_row_id' =>
                null,

            'review_decision' =>
                'pending',

            'review_notes' =>
                null,

            'reviewed_by' =>
                null,

            'reviewed_at' =>
                null,

            'posted_contribution_id' =>
                null,

            'posted_at' =>
                null,

            'source_reference' =>
                $transaction[
                    'source_reference'
                ]
                ??
                null,

            'comments' =>
                null,

            'created_at' =>
                now(),

            'updated_at' =>
                now(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Existing Contribution Cache
    |--------------------------------------------------------------------------
    */

    private function historicalContributionAlreadyExists(
        int $memberId,
        int $employerId,
        int $year,
        int $month,
        string $transactionType,
        array &$cache
    ): bool {
        $cacheKey =
            $memberId
            .
            '|'
            .
            $employerId;

        /*
        |--------------------------------------------------------------------------
        | One Query Per Member + Employer
        |--------------------------------------------------------------------------
        */

        if (
            !isset(
                $cache[
                    $cacheKey
                ]
            )
        ) {
            $existing =
                [];

            DB::table(
                'member_contributions'
            )
                ->where(
                    'member_id',
                    $memberId
                )
                ->where(
                    'employer_id',
                    $employerId
                )
                ->select([
                    'period_year',
                    'period_month',
                    'transaction_type',
                ])
                ->orderBy(
                    'id'
                )
                ->get()
                ->each(
                    function (
                        $row
                    ) use (
                        &$existing
                    ): void {
                        $key =
                            (int) $row->period_year
                            .
                            '|'
                            .
                            (int) $row->period_month
                            .
                            '|'
                            .
                            $this->normaliseTransactionType(
                                $row->transaction_type
                            );

                        $existing[
                            $key
                        ] =
                            true;
                    }
                );

            $cache[
                $cacheKey
            ] =
                $existing;
        }

        $periodKey =
            $year
            .
            '|'
            .
            $month
            .
            '|'
            .
            $this->normaliseTransactionType(
                $transactionType
            );

        return isset(
            $cache[
                $cacheKey
            ][
                $periodKey
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Historical New Member Validation
    |--------------------------------------------------------------------------
    */

    private function validateHistoricalNewMember(
        array $data,
        array &$errors,
        array &$warnings
    ): void {
        if (
            blank(
                $data[
                    'surname'
                ]
                ??
                null
            )
        ) {
            $errors[] =
                'Surname is required before an unmatched historical contributor can be created.';
        }

        if (
            blank(
                $data[
                    'first_names'
                ]
                ??
                null
            )
        ) {
            $errors[] =
                'First Name(s) are required before an unmatched historical contributor can be created.';
        }

        if (
            blank(
                $data[
                    'staff_number'
                ]
                ??
                null
            )
        ) {
            $warnings[] =
                'Staff Number is not available for this historical contributor.';
        }

        /*
        |--------------------------------------------------------------------------
        | Phone / Email / Address Are NOT Mandatory
        |--------------------------------------------------------------------------
        */

        if (
            blank(
                $data[
                    'penad_member_number'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'national_id'
                ]
                ??
                null
            )
            &&
            blank(
                $data[
                    'staff_number'
                ]
                ??
                null
            )
        ) {
            $warnings[] =
                'This historical contributor has weak identifying information and should be reviewed carefully.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Service Window
    |--------------------------------------------------------------------------
    */

    private function determineServiceWindow(
        array $periods
    ): ?array {
        $first =
            null;

        $last =
            null;

        foreach (
            $periods
            as $period
        ) {
            $status =
                $period[
                    'service_status'
                ]
                ??
                'blank';

            if (
                $status
                !==
                'contributed'
                &&
                $status
                !==
                'zero_contribution'
            ) {
                continue;
            }

            $value =
                (
                    (
                        (int) $period[
                            'period_year'
                        ]
                    )
                    *
                    100
                )
                +
                (int) $period[
                    'period_month'
                ];

            if (
                $first
                ===
                null
                ||
                $value
                <
                $first
            ) {
                $first =
                    $value;
            }

            if (
                $last
                ===
                null
                ||
                $value
                >
                $last
            ) {
                $last =
                    $value;
            }
        }

        if (
            $first
            ===
            null
        ) {
            return null;
        }

        return [
            'first' =>
                $first,

            'last' =>
                $last,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Inside Service Window
    |--------------------------------------------------------------------------
    */

    private function periodFallsInsideServiceWindow(
        array $transaction,
        ?array $serviceWindow
    ): bool {
        if (!$serviceWindow) {
            return false;
        }

        $value =
            (
                (
                    (int) $transaction[
                        'period_year'
                    ]
                )
                *
                100
            )
            +
            (int) $transaction[
                'period_month'
            ];

        return
            $value
            >
            $serviceWindow[
                'first'
            ]
            &&
            $value
            <
            $serviceWindow[
                'last'
            ];
    }

    /*
    |--------------------------------------------------------------------------
    | New Member Identity
    |--------------------------------------------------------------------------
    */

    private function makeNewMemberIdentityKey(
        int $employerId,
        array $data
    ): string {
        /*
        |--------------------------------------------------------------------------
        | PenAd First
        |--------------------------------------------------------------------------
        */

        $penad =
            $this->normalize(
                $data[
                    'penad_member_number'
                ]
                ??
                $data[
                    'legacy_member_number'
                ]
                ??
                null
            );

        if ($penad) {
            return hash(
                'sha256',
                'employer:'
                . $employerId
                . '|penad:'
                . $penad
            );
        }

        /*
        |--------------------------------------------------------------------------
        | PENERP
        |--------------------------------------------------------------------------
        */

        $penerp =
            $this->normalize(
                $data[
                    'penerp_member_number'
                ]
                ??
                null
            );

        if ($penerp) {
            return hash(
                'sha256',
                'penerp:'
                . $penerp
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fundworx
        |--------------------------------------------------------------------------
        */

        $fundworx =
            $this->normalize(
                $data[
                    'fundworx_member_number'
                ]
                ??
                null
            );

        if ($fundworx) {
            return hash(
                'sha256',
                'fundworx:'
                . $fundworx
            );
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
                ??
                null
            );

        if ($nationalId) {
            return hash(
                'sha256',
                'national:'
                . $nationalId
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Staff Number Is NOT Enough By Itself
        |--------------------------------------------------------------------------
        */

        return hash(
            'sha256',
            implode(
                '|',
                [
                    'employer:'
                    . $employerId,

                    'staff:'
                    . (
                        $this->normalize(
                            $data[
                                'staff_number'
                            ]
                            ??
                            null
                        )
                        ??
                        ''
                    ),

                    'surname:'
                    . (
                        $this->normalize(
                            $data[
                                'surname'
                            ]
                            ??
                            null
                        )
                        ??
                        ''
                    ),

                    'first:'
                    . (
                        $this->normalize(
                            $data[
                                'first_names'
                            ]
                            ??
                            null
                        )
                        ??
                        ''
                    ),

                    'dob:'
                    . (
                        $data[
                            'date_of_birth'
                        ]
                        ??
                        ''
                    ),
                ]
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Flush Staging
    |--------------------------------------------------------------------------
    */

    private function flushInsertBuffer(
        array &$buffer
    ): void {
        if (
            empty(
                $buffer
            )
        ) {
            return;
        }

        foreach (
            array_chunk(
                $buffer,
                self::INSERT_CHUNK
            )
            as $chunk
        ) {
            DB::table(
                'historical_contribution_import_rows'
            )
                ->insert(
                    $chunk
                );
        }

        $buffer = [];

        gc_collect_cycles();
    }

    /*
    |--------------------------------------------------------------------------
    | Transaction Type
    |--------------------------------------------------------------------------
    */

    private function normaliseTransactionType(
        mixed $value
    ): string {
        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );

        return match ($value) {
            'take_on',
            'take-on',
            'take on' =>
                'take_on',

            default =>
                'expected',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Decimal 4
    |--------------------------------------------------------------------------
    */

    private function decimal4OrNull(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            (
                is_string(
                    $value
                )
                &&
                trim(
                    $value
                )
                ===
                ''
            )
        ) {
            return null;
        }

        if (
            is_string(
                $value
            )
        ) {
            $value =
                str_replace(
                    [
                        ',',
                        '$',
                        ' ',
                    ],
                    '',
                    trim(
                        $value
                    )
                );
        }

        if (
            !is_numeric(
                $value
            )
        ) {
            return null;
        }

        return number_format(
            (float) $value,
            4,
            '.',
            ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Decimal 6
    |--------------------------------------------------------------------------
    */

    private function decimal6OrNull(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            (
                is_string(
                    $value
                )
                &&
                trim(
                    $value
                )
                ===
                ''
            )
        ) {
            return null;
        }

        if (
            is_string(
                $value
            )
        ) {
            $value =
                str_replace(
                    [
                        ',',
                        '%',
                        ' ',
                    ],
                    '',
                    trim(
                        $value
                    )
                );
        }

        if (
            !is_numeric(
                $value
            )
        ) {
            return null;
        }

        return number_format(
            (float) $value,
            6,
            '.',
            ''
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize
    |--------------------------------------------------------------------------
    */

    private function normalize(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            strtoupper(
                trim(
                    (string) $value
                )
            );

        return
            $value !== ''
                ? $value
                : null;
    }
}