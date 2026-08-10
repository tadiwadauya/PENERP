<?php

namespace App\Services\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\PensionsAdministration\Updates\MemberEmployment;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class MembershipImportValidationService
{
    private const CHUNK_SIZE = 500;

    private const EXPECTED_HEADERS = [
        'import_action',
        'penerp_member_number',
        'penad_member_number',
        'fundworx_member_number',

        'penerp_employer_number',
        'penad_employer_number',
        'fundworx_employer_number',

        'title',
        'surname',
        'first_names',
        'other_names',
        'maiden_name',

        'national_id',
        'date_of_birth',
        'gender',
        'marital_status',
        'occupation',

        'email',
        'secondary_email',
        'cell_number',
        'secondary_cell_number',

        'date_joined_fund',
        'membership_status',

        'staff_number',
        'vote_number',
        'date_joined_employer',

        'department',
        'branch',

        'physical_address_1',
        'physical_address_2',
        'physical_address_3',
        'physical_suburb',
        'physical_city',
        'physical_country',

        'postal_address_1',
        'postal_address_2',
        'postal_address_3',
        'postal_city',
        'postal_country',
    ];

    /*
    |--------------------------------------------------------------------------
    | Current Excel Duplicate Tracking
    |--------------------------------------------------------------------------
    */

    private array $seenNationalIds = [];

    private array $seenPenerpNumbers = [];

    private array $seenPenadNumbers = [];

    private array $seenFundworxNumbers = [];

    private array $seenStaffNumbers = [];

    /*
    |--------------------------------------------------------------------------
    | Existing Database National IDs
    |--------------------------------------------------------------------------
    */

    private array $existingNationalIds = [];


    /*
    |--------------------------------------------------------------------------
    | Process Import
    |--------------------------------------------------------------------------
    */

    public function process(
        MembershipImportBatch $batch
    ): void {
        $disk = Storage::disk('local');

        if (!$disk->exists($batch->file_path)) {
            throw new RuntimeException(
                'The uploaded Excel file could not be found in storage. Stored path: '
                . $batch->file_path
            );
        }

        $path = $disk->path(
            $batch->file_path
        );

        if (!is_file($path)) {
            throw new RuntimeException(
                'The uploaded Excel file exists in Laravel storage but could not be resolved to a physical file.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reset Previous Validation
        |--------------------------------------------------------------------------
        */

        $batch->rows()->delete();

        $this->seenNationalIds = [];

        $this->seenPenerpNumbers = [];
        $this->seenPenadNumbers = [];
        $this->seenFundworxNumbers = [];

        $this->seenStaffNumbers = [];

        $this->existingNationalIds = [];

        $batch->update([
            'status' => 'processing',

            'started_at' => now(),
            'completed_at' => null,

            'failure_reason' => null,

            'total_rows' => 0,
            'processed_rows' => 0,

            'valid_rows' => 0,
            'warning_rows' => 0,
            'error_rows' => 0,
            'duplicate_rows' => 0,

            'approved_rows' => 0,
            'rejected_rows' => 0,

            'progress_percentage' => 0,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Workbook Information
        |--------------------------------------------------------------------------
        */

        $reader = IOFactory::createReaderForFile(
            $path
        );

        $worksheetInfo = $reader->listWorksheetInfo(
            $path
        );

        if (empty($worksheetInfo)) {
            throw new RuntimeException(
                'No worksheet was found in the Excel file.'
            );
        }

        $sheetInfo = collect($worksheetInfo)
            ->firstWhere(
                'worksheetName',
                'Member Import Template'
            )
            ?? $worksheetInfo[0];

        $sheetName = $sheetInfo['worksheetName'];

        $highestRow = (int) $sheetInfo['totalRows'];

        if ($highestRow < 2) {
            throw new RuntimeException(
                'The Excel file contains no membership records.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Headers
        |--------------------------------------------------------------------------
        */

        $this->validateHeaders(
            $path,
            $sheetName
        );

        /*
        |--------------------------------------------------------------------------
        | Build Lookup Maps
        |--------------------------------------------------------------------------
        */

        $employerMaps =
            $this->buildEmployerMaps();

        $this->buildExistingNationalIdMaps();

        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $actualRows = 0;

        $processedRows = 0;

        $validRows = 0;
        $warningRows = 0;
        $errorRows = 0;
        $duplicateRows = 0;

        $batch->update([
            'status' => 'validating',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Read Workbook in Chunks
        |--------------------------------------------------------------------------
        */

        for (
            $startRow = 2;
            $startRow <= $highestRow;
            $startRow += self::CHUNK_SIZE
        ) {
            $endRow = min(
                $startRow + self::CHUNK_SIZE - 1,
                $highestRow
            );

            $candidates = $this->readChunk(
                $path,
                $sheetName,
                $startRow,
                $endRow
            );

            if (empty($candidates)) {
                $this->updateProgress(
                    $batch,
                    $endRow,
                    $highestRow,
                    $processedRows,
                    $validRows,
                    $warningRows,
                    $errorRows,
                    $duplicateRows
                );

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Existing Member References
            |--------------------------------------------------------------------------
            */

            $existingMembers =
                $this->findExistingMembers(
                    $candidates
                );

            /*
            |--------------------------------------------------------------------------
            | Existing Staff Numbers
            |--------------------------------------------------------------------------
            */

            $existingStaff =
                $this->findExistingStaffNumbers(
                    $candidates,
                    $employerMaps,
                    $batch
                );

            $insertRows = [];

            foreach ($candidates as $candidate) {
                $rowNumber =
                    $candidate['row_number'];

                $raw =
                    $candidate['raw'];

                if ($this->isBlankRow($raw)) {
                    continue;
                }

                $actualRows++;
                $processedRows++;

                $normalized =
                    $this->normalizeRow(
                        $raw
                    );

                $errors = [];
                $warnings = [];
                $duplicateReasons = [];

                $duplicateStatus = 'none';
                $duplicateScore = null;

                $matchedMemberId = null;

                /*
                |--------------------------------------------------------------------------
                | Import Action
                |--------------------------------------------------------------------------
                */

                $importAction = strtoupper(
                    trim(
                        $normalized['import_action']
                        ?: 'AUTO'
                    )
                );

                if (!in_array(
                    $importAction,
                    [
                        'AUTO',
                        'CREATE',
                        'UPDATE',
                    ],
                    true
                )) {
                    $errors[] =
                        'Import Action must be AUTO, CREATE or UPDATE.';
                }

                /*
                |--------------------------------------------------------------------------
                | Required Member Data
                |--------------------------------------------------------------------------
                */

                if (!$normalized['surname']) {
                    $errors[] =
                        'Surname is required.';
                }

                if (!$normalized['first_names']) {
                    $errors[] =
                        'First names are required.';
                }

                if (!$normalized['membership_status']) {
                    $errors[] =
                        'Membership status is required.';

                } elseif (!in_array(
                    $normalized['membership_status'],
                    [
                        'active',
                        'dormant',
                        'inactive',
                        'suspended',
                    ],
                    true
                )) {
                    $errors[] =
                        'Invalid membership status.';
                }

                /*
                |--------------------------------------------------------------------------
                | National ID Required for Active Members
                |--------------------------------------------------------------------------
                */

              if (
    $normalized['membership_status'] === 'active'
    && empty($normalized['national_id'])
) {
    $errors[] =
        'National ID is required for an active member.';
}

                /*
                |--------------------------------------------------------------------------
                | Dates
                |--------------------------------------------------------------------------
                */

                if (
                    $raw['date_of_birth']
                    && !$normalized['date_of_birth']
                ) {
                    $errors[] =
                        'Date of birth is invalid.';
                }

                if (
                    $raw['date_joined_fund']
                    && !$normalized['date_joined_fund']
                ) {
                    $errors[] =
                        'Date joined fund is invalid.';
                }

                if (
                    $raw['date_joined_employer']
                    && !$normalized['date_joined_employer']
                ) {
                    $errors[] =
                        'Date joined employer is invalid.';
                }

                /*
                |--------------------------------------------------------------------------
                | Email
                |--------------------------------------------------------------------------
                */

                if (
                    $normalized['email']
                    && !filter_var(
                        $normalized['email'],
                        FILTER_VALIDATE_EMAIL
                    )
                ) {
                    $errors[] =
                        'Primary email address is invalid.';
                }

                if (
                    $normalized['secondary_email']
                    && !filter_var(
                        $normalized['secondary_email'],
                        FILTER_VALIDATE_EMAIL
                    )
                ) {
                    $errors[] =
                        'Secondary email address is invalid.';
                }

                /*
                |--------------------------------------------------------------------------
                | Employer Matching
                |--------------------------------------------------------------------------
                |
                | PENAD EMPLOYER NUMBER IS AUTHORITATIVE FOR MIGRATION.
                |
                */

                $employerResult =
                    $this->resolveEmployer(
                        $batch,
                        $normalized,
                        $employerMaps
                    );

                $matchedEmployerId =
                    $employerResult['id'];

                if ($employerResult['error']) {
                    $errors[] =
                        $employerResult['error'];
                }

                /*
                |--------------------------------------------------------------------------
                | Active Member Requires Employer
                |--------------------------------------------------------------------------
                */

                if (
                    $normalized['membership_status'] === 'active'
                    && !$matchedEmployerId
                ) {
                    $penadEmployerNumber =
                        $normalized['penad_employer_number']
                        ?? null;

                    if ($penadEmployerNumber) {
                        $errors[] =
                            'Active member requires a valid employer. PenAd Employer Number '
                            . $penadEmployerNumber
                            . ' was not found.';

                    } else {
                        $errors[] =
                            'Active member requires a valid employer. PenAd Employer Number was not supplied.';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Vote Number
                |--------------------------------------------------------------------------
                */

                if ($matchedEmployerId) {
                    $employer =
                        $employerMaps['by_id'][$matchedEmployerId]
                        ?? null;

                    if (
                        $employer
                        && $employer->employerGroup
                        && $employer->employerGroup->vote_number_required
                        && !$normalized['vote_number']
                    ) {
                        $errors[] =
                            'Vote number is required for this employer group.';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Member Matches
                |--------------------------------------------------------------------------
                */

                $matchIds = [];

                /*
                |--------------------------------------------------------------------------
                | PENERP Member Number
                |--------------------------------------------------------------------------
                */

                if (
                    $normalized['penerp_member_number']
                ) {
                    $key =
                        $this->memberReferenceKey(
                            $normalized['penerp_member_number']
                        );

                    if (
                        isset(
                            $existingMembers['penerp'][$key]
                        )
                    ) {
                        $matchIds[] =
                            $existingMembers['penerp'][$key];

                        $duplicateReasons[] =
                            'PENERP member number already exists.';
                    }

                    if (
                        isset(
                            $this->seenPenerpNumbers[$key]
                        )
                    ) {
                        $duplicateReasons[] =
                            'PENERP member number is repeated in this file. First seen on row '
                            . $this->seenPenerpNumbers[$key]
                            . '.';

                        $duplicateStatus =
                            'exact';

                        $duplicateScore =
                            100;

                    } else {
                        $this->seenPenerpNumbers[$key] =
                            $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | NATIONAL ID DUPLICATE RULE
                |--------------------------------------------------------------------------
                |
                | EXACT DUPLICATE:
                |
                | 24-111839V26
                | 24-111839 V 26
                | 24-111839-V-26
                | 024-111839V26
                | 07-111839V26
                | 07-111839-V-26
                |
                | They all resolve to:
                |
                | ZW:111839V26
                |
                |
                | NOT DUPLICATES:
                |
                | 24-111839V26
                | 24-111839Y26
                |
                | Because:
                |
                | V != Y
                |
                | They are NOT even marked "possible".
                |
                |
                | Numeric IDs:
                |
                | '63927705027
                | 63927705027
                |
                | resolve to:
                |
                | NUM:63927705027
                |
                */

                $nationalExact =
                    $normalized['national_id_exact_key'];

                if ($nationalExact) {
                    /*
                    |--------------------------------------------------------------------------
                    | Existing Database Exact ID
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset(
                            $this->existingNationalIds[
                                $nationalExact
                            ]
                        )
                    ) {
                        $memberId =
                            $this->existingNationalIds[
                                $nationalExact
                            ];

                        $matchIds[] =
                            $memberId;

                        $duplicateReasons[] =
                            'National ID matches an existing member using ID '
                            . $this->formatNationalIdForMessage(
                                $normalized
                            )
                            . '.';

                        $duplicateStatus =
                            'exact';

                        $duplicateScore =
                            100;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Same Exact ID Inside Excel
                    |--------------------------------------------------------------------------
                    */

                    if (
                        isset(
                            $this->seenNationalIds[
                                $nationalExact
                            ]
                        )
                    ) {
                        $duplicateReasons[] =
                            'National ID is repeated in this file. ID '
                            . $this->formatNationalIdForMessage(
                                $normalized
                            )
                            . ' was first seen on row '
                            . $this->seenNationalIds[
                                $nationalExact
                            ]
                            . '.';

                        $duplicateStatus =
                            'exact';

                        $duplicateScore =
                            100;

                    } else {
                        $this->seenNationalIds[
                            $nationalExact
                        ] =
                            $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | PenAd Member Number
                |--------------------------------------------------------------------------
                */

                if (
                    $normalized['penad_member_number']
                ) {
                    $key =
                        $this->memberReferenceKey(
                            $normalized['penad_member_number']
                        );

                    if (
                        isset(
                            $existingMembers['penad'][$key]
                        )
                    ) {
                        $matchIds[] =
                            $existingMembers['penad'][$key];

                        $duplicateReasons[] =
                            'PenAd member number already exists.';
                    }

                    if (
                        isset(
                            $this->seenPenadNumbers[$key]
                        )
                    ) {
                        $duplicateReasons[] =
                            'PenAd member number is repeated in this file. First seen on row '
                            . $this->seenPenadNumbers[$key]
                            . '.';

                        $duplicateStatus =
                            'exact';

                        $duplicateScore =
                            100;

                    } else {
                        $this->seenPenadNumbers[$key] =
                            $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Fundworx Member Number
                |--------------------------------------------------------------------------
                */

                if (
                    $normalized['fundworx_member_number']
                ) {
                    $key =
                        $this->memberReferenceKey(
                            $normalized['fundworx_member_number']
                        );

                    if (
                        isset(
                            $existingMembers['fundworx'][$key]
                        )
                    ) {
                        $matchIds[] =
                            $existingMembers['fundworx'][$key];

                        $duplicateReasons[] =
                            'Fundworx member number already exists.';
                    }

                    if (
                        isset(
                            $this->seenFundworxNumbers[$key]
                        )
                    ) {
                        $duplicateReasons[] =
                            'Fundworx member number is repeated in this file. First seen on row '
                            . $this->seenFundworxNumbers[$key]
                            . '.';

                        $duplicateStatus =
                            'exact';

                        $duplicateScore =
                            100;

                    } else {
                        $this->seenFundworxNumbers[$key] =
                            $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve Existing Member
                |--------------------------------------------------------------------------
                */

                $matchIds =
                    array_values(
                        array_unique(
                            array_filter(
                                $matchIds
                            )
                        )
                    );

                if (
                    count($matchIds) > 1
                ) {
                    $errors[] =
                        'The supplied member references match different existing members. Manual review is required.';

                } elseif (
                    count($matchIds) === 1
                ) {
                    $matchedMemberId =
                        $matchIds[0];

                    $duplicateStatus =
                        'exact';

                    $duplicateScore =
                        100;
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE Requires Existing Member
                |--------------------------------------------------------------------------
                */

                if (
                    $importAction === 'UPDATE'
                    && !$matchedMemberId
                ) {
                    $errors[] =
                        'Import Action is UPDATE but no existing member could be identified.';
                }

                /*
                |--------------------------------------------------------------------------
                | Staff Number
                |--------------------------------------------------------------------------
                */

                if (
                    $matchedEmployerId
                    && $normalized['staff_number']
                ) {
                    $staffKey =
                        $matchedEmployerId
                        . '|'
                        . $this->memberReferenceKey(
                            $normalized['staff_number']
                        );

                    if (
                        isset(
                            $this->seenStaffNumbers[
                                $staffKey
                            ]
                        )
                    ) {
                        $errors[] =
                            'Staff number is repeated for the same employer in this file. First seen on row '
                            . $this->seenStaffNumbers[
                                $staffKey
                            ]
                            . '.';

                    } else {
                        $this->seenStaffNumbers[
                            $staffKey
                        ] =
                            $rowNumber;
                    }

                    if (
                        isset(
                            $existingStaff[
                                $staffKey
                            ]
                        )
                    ) {
                        $existingEmployment =
                            $existingStaff[
                                $staffKey
                            ];

                        if (
                            !$matchedMemberId
                            ||
                            (int)
                            $existingEmployment['member_id']
                            !==
                            (int)
                            $matchedMemberId
                        ) {
                            $errors[] =
                                'Staff number is already assigned to another current member under this employer.';
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Remove Duplicate Messages
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

                $duplicateReasons =
                    array_values(
                        array_unique(
                            $duplicateReasons
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Exact Duplicate Warning
                |--------------------------------------------------------------------------
                */

                if (
                    $duplicateStatus === 'exact'
                    && empty($errors)
                ) {
                    $warnings[] =
                        'An exact existing or repeated member record was detected. Review before import.';
                }

                /*
                |--------------------------------------------------------------------------
                | Validation Status
                |--------------------------------------------------------------------------
                */

                if (!empty($errors)) {
                    $validationStatus =
                        'error';

                    $errorRows++;

                } elseif (!empty($warnings)) {
                    $validationStatus =
                        'warning';

                    $warningRows++;

                } else {
                    $validationStatus =
                        'valid';

                    $validRows++;
                }

                if (
                    $duplicateStatus === 'exact'
                ) {
                    $duplicateRows++;
                }

                /*
                |--------------------------------------------------------------------------
                | Stage Row
                |--------------------------------------------------------------------------
                */

                $insertRows[] = [
                    'import_batch_id' =>
                        $batch->id,

                    'row_number' =>
                        $rowNumber,

                    'import_action' =>
                        $importAction,

                    'raw_data' =>
                        json_encode(
                            $raw,
                            JSON_UNESCAPED_UNICODE
                        ),

                    'normalized_data' =>
                        json_encode(
                            $normalized,
                            JSON_UNESCAPED_UNICODE
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

                    'matched_employer_id' =>
                        $matchedEmployerId,

                    'duplicate_status' =>
                        $duplicateStatus,

                    'matched_member_id' =>
                        $matchedMemberId,

                    'duplicate_score' =>
                        $duplicateScore,

                    'duplicate_reasons' =>
                        $duplicateReasons
                            ? json_encode(
                                $duplicateReasons,
                                JSON_UNESCAPED_UNICODE
                            )
                            : null,

                    'review_decision' =>
                        'pending',

                    'created_at' =>
                        now(),

                    'updated_at' =>
                        now(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | SQL Server Safe Insert
            |--------------------------------------------------------------------------
            */

            foreach (
                array_chunk(
                    $insertRows,
                    50
                )
                as $insertChunk
            ) {
                DB::table(
                    'membership_import_rows'
                )->insert(
                    $insertChunk
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Progress
            |--------------------------------------------------------------------------
            */

            $this->updateProgress(
                $batch,
                $endRow,
                $highestRow,
                $processedRows,
                $validRows,
                $warningRows,
                $errorRows,
                $duplicateRows
            );

            unset(
                $candidates,
                $insertRows
            );

            gc_collect_cycles();
        }

        /*
        |--------------------------------------------------------------------------
        | No Data
        |--------------------------------------------------------------------------
        */

        if ($actualRows === 0) {
            throw new RuntimeException(
                'No populated membership rows were found in the workbook.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Complete
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'total_rows' =>
                $actualRows,

            'processed_rows' =>
                $processedRows,

            'valid_rows' =>
                $validRows,

            'warning_rows' =>
                $warningRows,

            'error_rows' =>
                $errorRows,

            'duplicate_rows' =>
                $duplicateRows,

            'progress_percentage' =>
                100,

            'status' =>
                'awaiting_review',

            'completed_at' =>
                now(),
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Validate Headers
    |--------------------------------------------------------------------------
    */

    private function validateHeaders(
        string $path,
        string $sheetName
    ): void {
        $reader =
            IOFactory::createReaderForFile(
                $path
            );

        $reader->setReadDataOnly(
            true
        );

        $reader->setLoadSheetsOnly([
            $sheetName,
        ]);

        $reader->setReadFilter(
            new ChunkReadFilter(
                1,
                1
            )
        );

        $spreadsheet =
            $reader->load(
                $path
            );

        $sheet =
            $spreadsheet->getSheetByName(
                $sheetName
            );

        if (!$sheet) {
            throw new RuntimeException(
                'The membership worksheet could not be opened.'
            );
        }

        $row =
            $sheet->rangeToArray(
                'A1:AM1',
                null,
                false,
                false
            )[0];

        $headers =
            array_map(
                fn ($value) =>
                    strtolower(
                        trim(
                            (string) $value
                        )
                    ),
                $row
            );

        if (
            $headers
            !==
            self::EXPECTED_HEADERS
        ) {
            $errors = [];

            foreach (
                self::EXPECTED_HEADERS
                as $index => $expected
            ) {
                $actual =
                    $headers[$index]
                    ?? '[missing]';

                if (
                    $actual
                    !==
                    $expected
                ) {
                    $errors[] =
                        'Column '
                        . ($index + 1)
                        . ' should be "'
                        . $expected
                        . '" but "'
                        . $actual
                        . '" was found.';
                }
            }

            $spreadsheet
                ->disconnectWorksheets();

            throw new RuntimeException(
                'Invalid membership import template. '
                . implode(
                    ' ',
                    array_slice(
                        $errors,
                        0,
                        8
                    )
                )
            );
        }

        $spreadsheet
            ->disconnectWorksheets();
    }


    /*
    |--------------------------------------------------------------------------
    | Read Excel Chunk
    |--------------------------------------------------------------------------
    */

    private function readChunk(
        string $path,
        string $sheetName,
        int $startRow,
        int $endRow
    ): array {
        $reader =
            IOFactory::createReaderForFile(
                $path
            );

        $reader->setReadDataOnly(
            true
        );

        $reader->setLoadSheetsOnly([
            $sheetName,
        ]);

        $reader->setReadFilter(
            new ChunkReadFilter(
                $startRow,
                self::CHUNK_SIZE
            )
        );

        $spreadsheet =
            $reader->load(
                $path
            );

        $sheet =
            $spreadsheet->getSheetByName(
                $sheetName
            );

        if (!$sheet) {
            $spreadsheet
                ->disconnectWorksheets();

            return [];
        }

        $rows =
            $sheet->rangeToArray(
                'A'
                . $startRow
                . ':AM'
                . $endRow,
                null,
                false,
                false
            );

        $result = [];

        foreach (
            $rows
            as $offset => $values
        ) {
            $values =
                array_pad(
                    array_slice(
                        $values,
                        0,
                        count(
                            self::EXPECTED_HEADERS
                        )
                    ),
                    count(
                        self::EXPECTED_HEADERS
                    ),
                    null
                );

            $result[] = [
                'row_number' =>
                    $startRow
                    + $offset,

                'raw' =>
                    array_combine(
                        self::EXPECTED_HEADERS,
                        $values
                    ),
            ];
        }

        $spreadsheet
            ->disconnectWorksheets();

        unset($spreadsheet);

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Row
    |--------------------------------------------------------------------------
    */

    private function normalizeRow(
        array $row
    ): array {
        $normalized = [];

        foreach (
            $row
            as $key => $value
        ) {
            if (is_string($value)) {
                $value =
                    trim(
                        $value
                    );
            }

            $normalized[$key] =
                $value === ''
                    ? null
                    : $value;
        }

        /*
        |--------------------------------------------------------------------------
        | Member References
        |--------------------------------------------------------------------------
        */

        foreach ([
            'penerp_member_number',
            'penad_member_number',
            'fundworx_member_number',
            'staff_number',
            'vote_number',
        ] as $field) {
            $normalized[$field] =
                $this->cleanReference(
                    $normalized[$field]
                    ?? null
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Employer References
        |--------------------------------------------------------------------------
        |
        | Normalise Excel values such as:
        |
        | 11
        | 11.0
        | '11
        | 011
        |
        | into a canonical employer reference.
        |
        */

        foreach ([
            'penerp_employer_number',
            'penad_employer_number',
            'fundworx_employer_number',
        ] as $field) {
            $normalized[$field] =
                $this->cleanEmployerReference(
                    $normalized[$field]
                    ?? null
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Names
        |--------------------------------------------------------------------------
        */

        $normalized['title'] =
            $this->cleanText(
                $normalized['title']
                ?? null
            );

        $normalized['surname'] =
            $this->cleanText(
                $normalized['surname']
                ?? null
            );

        $normalized['first_names'] =
            $this->cleanText(
                $normalized['first_names']
                ?? null
            );

        $normalized['other_names'] =
            $this->cleanText(
                $normalized['other_names']
                ?? null
            );

        $normalized['maiden_name'] =
            $this->cleanText(
                $normalized['maiden_name']
                ?? null
            );

        /*
        |--------------------------------------------------------------------------
        | Membership Status
        |--------------------------------------------------------------------------
        */

        $normalized['membership_status'] =
            strtolower(
                trim(
                    (string)
                    (
                        $normalized['membership_status']
                        ?? ''
                    )
                )
            )
            ?: null;

        /*
        |--------------------------------------------------------------------------
        | Import Action
        |--------------------------------------------------------------------------
        */

        $normalized['import_action'] =
            strtoupper(
                trim(
                    (string)
                    (
                        $normalized['import_action']
                        ?? 'AUTO'
                    )
                )
            );

        /*
        |--------------------------------------------------------------------------
        | National ID
        |--------------------------------------------------------------------------
        */

        $normalized['national_id'] =
            $this->cleanNationalId(
                $normalized['national_id']
                ?? null
            );

        $normalized['national_id_normalized'] =
            $this->normalizeNationalIdFull(
                $normalized['national_id']
            );

        $nationalIdParts =
            $this->extractNationalIdParts(
                $normalized['national_id']
            );

        $normalized['national_id_type'] =
            $nationalIdParts['type'];

        $normalized['national_id_exact_key'] =
            $nationalIdParts['exact_key'];

        $normalized['national_id_serial'] =
            $nationalIdParts['serial'];

        $normalized['national_id_letter'] =
            $nationalIdParts['letter'];

        $normalized['national_id_suffix'] =
            $nationalIdParts['suffix'];

        $normalized['national_id_numeric'] =
            $nationalIdParts['numeric_id'];

        /*
        |--------------------------------------------------------------------------
        | Dates
        |--------------------------------------------------------------------------
        */

        $normalized['date_of_birth'] =
            $this->normalizeDate(
                $normalized['date_of_birth']
                ?? null
            );

        $normalized['date_joined_fund'] =
            $this->normalizeDate(
                $normalized['date_joined_fund']
                ?? null
            );

        $normalized['date_joined_employer'] =
            $this->normalizeDate(
                $normalized['date_joined_employer']
                ?? null
            );

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        $normalized['email'] =
            $normalized['email']
                ? strtolower(
                    trim(
                        (string)
                        $normalized['email']
                    )
                )
                : null;

        $normalized['secondary_email'] =
            $normalized['secondary_email']
                ? strtolower(
                    trim(
                        (string)
                        $normalized['secondary_email']
                    )
                )
                : null;

        return $normalized;
    }


    /*
    |--------------------------------------------------------------------------
    | National ID Parsing
    |--------------------------------------------------------------------------
    |
    | Zimbabwe IDs:
    |
    | 24-111839V26
    | 07-111839-V-26
    | 024-111839 V 26
    |
    | all become:
    |
    | ZW:111839V26
    |
    |
    | Different letters are NOT duplicates:
    |
    | ZW:111839V26
    | ZW:111839Y26
    |
    |
    | Numeric IDs:
    |
    | '63927705027
    | 63927705027
    |
    | both become:
    |
    | NUM:63927705027
    |
    */

    private function extractNationalIdParts(
        mixed $value
    ): array {
        $empty = [
            'type' => null,
            'exact_key' => null,
            'serial' => null,
            'letter' => null,
            'suffix' => null,
            'numeric_id' => null,
        ];

        if ($value === null) {
            return $empty;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return $empty;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Excel Text Apostrophe
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                "/^'+/",
                '',
                $value
            );

        $value =
            trim(
                $value
            );

        if ($value === '') {
            return $empty;
        }

        $clean =
            strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $value
                )
            );

        if ($clean === '') {
            return $empty;
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric National ID
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^\d+$/',
                $clean
            )
        ) {
            return [
                'type' =>
                    'numeric',

                'exact_key' =>
                    'NUM:'
                    . $clean,

                'serial' =>
                    null,

                'letter' =>
                    null,

                'suffix' =>
                    null,

                'numeric_id' =>
                    $clean,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Zimbabwe-Style National ID
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/(\d{6})([A-Z])(\d{2})$/',
                $clean,
                $matches
            )
        ) {
            $serial =
                $matches[1];

            $letter =
                $matches[2];

            $suffix =
                $matches[3];

            return [
                'type' =>
                    'zimbabwe',

                'exact_key' =>
                    'ZW:'
                    . $serial
                    . $letter
                    . $suffix,

                'serial' =>
                    $serial,

                'letter' =>
                    $letter,

                'suffix' =>
                    $suffix,

                'numeric_id' =>
                    null,
            ];
        }

        return $empty;
    }


    /*
    |--------------------------------------------------------------------------
    | Clean National ID
    |--------------------------------------------------------------------------
    */

    private function cleanNationalId(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Remove Only Leading Excel Apostrophe
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                "/^'+/",
                '',
                $value
            );

        $value =
            trim(
                $value
            );

        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Full National ID Normalization
    |--------------------------------------------------------------------------
    */

    private function normalizeNationalIdFull(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        $value =
            preg_replace(
                "/^'+/",
                '',
                $value
            );

        $value =
            strtoupper(
                preg_replace(
                    '/[^A-Z0-9]/',
                    '',
                    $value
                )
            );

        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Format National ID for Error Messages
    |--------------------------------------------------------------------------
    */

    private function formatNationalIdForMessage(
        array $normalized
    ): string {
        if (
            ($normalized['national_id_type'] ?? null)
            === 'numeric'
        ) {
            return
                $normalized['national_id_numeric']
                ?? $normalized['national_id']
                ?? 'Unknown';
        }

        $serial =
            $normalized['national_id_serial']
            ?? null;

        $letter =
            $normalized['national_id_letter']
            ?? null;

        $suffix =
            $normalized['national_id_suffix']
            ?? null;

        if (
            $serial
            && $letter
            && $suffix
        ) {
            return
                $serial
                . '-'
                . $letter
                . '-'
                . $suffix;
        }

        return
            $normalized['national_id']
            ?? 'Unknown';
    }


    /*
    |--------------------------------------------------------------------------
    | Existing National IDs
    |--------------------------------------------------------------------------
    */

    private function buildExistingNationalIdMaps(): void
    {
        $this->existingNationalIds = [];

        $members =
            Member::query()
                ->select([
                    'id',
                    'national_id',
                    'national_id_normalized',
                ])
                ->where(function ($query) {
                    $query
                        ->whereNotNull(
                            'national_id'
                        )
                        ->orWhereNotNull(
                            'national_id_normalized'
                        );
                })
                ->cursor();

        foreach ($members as $member) {
            $source =
                $member->national_id
                ?: $member->national_id_normalized;

            $parts =
                $this->extractNationalIdParts(
                    $source
                );

            if (!$parts['exact_key']) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Exact Match Only
            |--------------------------------------------------------------------------
            |
            | There is deliberately NO six-digit serial similarity map.
            |
            | Different letters are separate IDs.
            |
            */

            if (
                !isset(
                    $this->existingNationalIds[
                        $parts['exact_key']
                    ]
                )
            ) {
                $this->existingNationalIds[
                    $parts['exact_key']
                ] =
                    $member->id;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Employer
    |--------------------------------------------------------------------------
    |
    | Important:
    |
    | 1. PenAd employer number is authoritative.
    | 2. If PenAd is present and matches, return immediately.
    | 3. Migrated employer_number is also treated as a PenAd alias because
    |    PenAd number became the PENERP employer number.
    |
    */

    private function resolveEmployer(
        MembershipImportBatch $batch,
        array $row,
        array $maps
    ): array {
        /*
        |--------------------------------------------------------------------------
        | Fixed Employer Batch
        |--------------------------------------------------------------------------
        */

        if ($batch->employer_id) {
            return [
                'id' =>
                    (int)
                    $batch->employer_id,

                'error' =>
                    null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PenAd Employer Number - Primary
        |--------------------------------------------------------------------------
        */

        $penad =
            $row['penad_employer_number']
            ?? null;

        if ($penad) {
            $key =
                $this->employerReferenceKey(
                    $penad
                );

            if (
                isset(
                    $maps['penad'][$key]
                )
            ) {
                return [
                    'id' =>
                        (int)
                        $maps['penad'][$key],

                    'error' =>
                        null,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | PenAd Was Supplied But Could Not Be Found
            |--------------------------------------------------------------------------
            |
            | Do not silently use an unrelated secondary employer.
            |
            */

            return [
                'id' =>
                    null,

                'error' =>
                    'PenAd Employer Number '
                    . $penad
                    . ' could not be identified in the employer register.',
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PENERP Employer Number - Fallback
        |--------------------------------------------------------------------------
        */

        $penerp =
            $row['penerp_employer_number']
            ?? null;

        if ($penerp) {
            $key =
                $this->employerReferenceKey(
                    $penerp
                );

            if (
                isset(
                    $maps['penerp'][$key]
                )
            ) {
                return [
                    'id' =>
                        (int)
                        $maps['penerp'][$key],

                    'error' =>
                        null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fundworx Employer Number - Fallback
        |--------------------------------------------------------------------------
        */

        $fundworx =
            $row['fundworx_employer_number']
            ?? null;

        if ($fundworx) {
            $key =
                $this->employerReferenceKey(
                    $fundworx
                );

            if (
                isset(
                    $maps['fundworx'][$key]
                )
            ) {
                return [
                    'id' =>
                        (int)
                        $maps['fundworx'][$key],

                    'error' =>
                        null,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Not Found
        |--------------------------------------------------------------------------
        */

        if ($penerp) {
            return [
                'id' =>
                    null,

                'error' =>
                    'PenAd Employer Number was not supplied and PENERP Employer Number '
                    . $penerp
                    . ' could not be identified.',
            ];
        }

        if ($fundworx) {
            return [
                'id' =>
                    null,

                'error' =>
                    'PenAd Employer Number was not supplied and Fundworx Employer Number '
                    . $fundworx
                    . ' could not be identified.',
            ];
        }

        return [
            'id' =>
                null,

            'error' =>
                'PenAd Employer Number was not supplied and no valid employer reference was provided.',
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Maps
    |--------------------------------------------------------------------------
    */

    private function buildEmployerMaps(): array
    {
        $employers =
            Employer::query()
                ->with(
                    'employerGroup'
                )
                ->get();

        $maps = [
            'penerp' => [],
            'penad' => [],
            'fundworx' => [],
            'by_id' => [],
        ];

        foreach (
            $employers
            as $employer
        ) {
            $maps['by_id'][
                $employer->id
            ] =
                $employer;

            /*
            |--------------------------------------------------------------------------
            | PENERP Employer Number
            |--------------------------------------------------------------------------
            */

            if (
                $employer->employer_number
            ) {
                $key =
                    $this->employerReferenceKey(
                        $employer->employer_number
                    );

                $maps['penerp'][$key] =
                    $employer->id;

                /*
                |--------------------------------------------------------------------------
                | IMPORTANT MIGRATION ALIAS
                |--------------------------------------------------------------------------
                |
                | We decided PenAd number becomes employer_number.
                |
                | Therefore employer_number must ALSO be searchable through
                | the PenAd employer lookup.
                |
                | This fixes employers such as 11 where the migrated PENERP
                | employer number is 11.
                |
                */

                if (
                    !isset(
                        $maps['penad'][$key]
                    )
                ) {
                    $maps['penad'][$key] =
                        $employer->id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Historical PenAd Employer Number
            |--------------------------------------------------------------------------
            */

            if (
                $employer->penad_employer_number
            ) {
                $key =
                    $this->employerReferenceKey(
                        $employer->penad_employer_number
                    );

                $maps['penad'][$key] =
                    $employer->id;

                /*
                |--------------------------------------------------------------------------
                | PenAd Number Also Acts as PENERP Migration Number
                |--------------------------------------------------------------------------
                */

                if (
                    !isset(
                        $maps['penerp'][$key]
                    )
                ) {
                    $maps['penerp'][$key] =
                        $employer->id;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Fundworx Employer Number
            |--------------------------------------------------------------------------
            */

            if (
                $employer->fundworx_employer_number
            ) {
                $key =
                    $this->employerReferenceKey(
                        $employer->fundworx_employer_number
                    );

                $maps['fundworx'][$key] =
                    $employer->id;
            }
        }

        return $maps;
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Reference Normalization
    |--------------------------------------------------------------------------
    |
    | All of these resolve to the same key:
    |
    | 11
    | 11.0
    | '11
    | 011
    | " 11 "
    |
    */

    private function cleanEmployerReference(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Excel Text Apostrophe
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                "/^'+/",
                '',
                $value
            );

        $value =
            trim(
                $value
            );

        if ($value === '') {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Excel Number Stored as 11.0
        |--------------------------------------------------------------------------
        */

        if (
            preg_match(
                '/^0*(\d+)(?:\.0+)?$/',
                $value,
                $matches
            )
        ) {
            $digits =
                ltrim(
                    $matches[1],
                    '0'
                );

            return
                $digits === ''
                    ? '0'
                    : $digits;
        }

        return strtoupper(
            preg_replace(
                '/\s+/',
                '',
                $value
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employer Lookup Key
    |--------------------------------------------------------------------------
    */

    private function employerReferenceKey(
        mixed $value
    ): string {
        return strtoupper(
            $this->cleanEmployerReference(
                $value
            )
            ?? ''
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Member References
    |--------------------------------------------------------------------------
    */

    private function findExistingMembers(
        array $candidates
    ): array {
        $penerp = [];
        $penad = [];
        $fundworx = [];

        foreach (
            $candidates
            as $candidate
        ) {
            $row =
                $this->normalizeRow(
                    $candidate['raw']
                );

            if (
                $row['penerp_member_number']
            ) {
                $penerp[] =
                    $row['penerp_member_number'];
            }

            if (
                $row['penad_member_number']
            ) {
                $penad[] =
                    $row['penad_member_number'];
            }

            if (
                $row['fundworx_member_number']
            ) {
                $fundworx[] =
                    $row['fundworx_member_number'];
            }
        }

        $penerp =
            array_values(
                array_unique(
                    array_filter(
                        $penerp
                    )
                )
            );

        $penad =
            array_values(
                array_unique(
                    array_filter(
                        $penad
                    )
                )
            );

        $fundworx =
            array_values(
                array_unique(
                    array_filter(
                        $fundworx
                    )
                )
            );

        if (
            empty($penerp)
            && empty($penad)
            && empty($fundworx)
        ) {
            return [
                'penerp' => [],
                'penad' => [],
                'fundworx' => [],
            ];
        }

        $query =
            Member::query();

        $query->where(
            function ($query) use (
                $penerp,
                $penad,
                $fundworx
            ) {
                $hasClause =
                    false;

                if ($penerp) {
                    $query->whereIn(
                        'member_number',
                        $penerp
                    );

                    $hasClause =
                        true;
                }

                if ($penad) {
                    $method =
                        $hasClause
                            ? 'orWhereIn'
                            : 'whereIn';

                    $query->{$method}(
                        'penad_member_number',
                        $penad
                    );

                    $hasClause =
                        true;
                }

                if ($fundworx) {
                    $method =
                        $hasClause
                            ? 'orWhereIn'
                            : 'whereIn';

                    $query->{$method}(
                        'fundworx_member_number',
                        $fundworx
                    );
                }
            }
        );

        $members =
            $query->get([
                'id',
                'member_number',
                'penad_member_number',
                'fundworx_member_number',
            ]);

        $maps = [
            'penerp' => [],
            'penad' => [],
            'fundworx' => [],
        ];

        foreach (
            $members
            as $member
        ) {
            if (
                $member->member_number
            ) {
                $maps['penerp'][
                    $this->memberReferenceKey(
                        $member->member_number
                    )
                ] =
                    $member->id;
            }

            if (
                $member->penad_member_number
            ) {
                $maps['penad'][
                    $this->memberReferenceKey(
                        $member->penad_member_number
                    )
                ] =
                    $member->id;
            }

            if (
                $member->fundworx_member_number
            ) {
                $maps['fundworx'][
                    $this->memberReferenceKey(
                        $member->fundworx_member_number
                    )
                ] =
                    $member->id;
            }
        }

        return $maps;
    }


    /*
    |--------------------------------------------------------------------------
    | Existing Staff Numbers
    |--------------------------------------------------------------------------
    */

    private function findExistingStaffNumbers(
        array $candidates,
        array $employerMaps,
        MembershipImportBatch $batch
    ): array {
        $employerIds = [];
        $staffNumbers = [];

        foreach (
            $candidates
            as $candidate
        ) {
            $row =
                $this->normalizeRow(
                    $candidate['raw']
                );

            if (
                !$row['staff_number']
            ) {
                continue;
            }

            if (
                $batch->employer_id
            ) {
                $resolved =
                    (int)
                    $batch->employer_id;

            } else {
                $resolved =
                    $this->resolveEmployerFromMaps(
                        $row,
                        $employerMaps
                    );
            }

            if (!$resolved) {
                continue;
            }

            $employerIds[] =
                $resolved;

            $staffNumbers[] =
                $row['staff_number'];
        }

        $employerIds =
            array_values(
                array_unique(
                    $employerIds
                )
            );

        $staffNumbers =
            array_values(
                array_unique(
                    $staffNumbers
                )
            );

        if (
            !$employerIds
            || !$staffNumbers
        ) {
            return [];
        }

        $records =
            MemberEmployment::query()
                ->where(
                    'is_current',
                    true
                )
                ->whereIn(
                    'employer_id',
                    $employerIds
                )
                ->whereIn(
                    'staff_number',
                    $staffNumbers
                )
                ->get([
                    'id',
                    'member_id',
                    'employer_id',
                    'staff_number',
                ]);

        $result = [];

        foreach (
            $records
            as $record
        ) {
            $result[
                $record->employer_id
                . '|'
                . $this->memberReferenceKey(
                    $record->staff_number
                )
            ] = [
                'id' =>
                    $record->id,

                'member_id' =>
                    $record->member_id,
            ];
        }

        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | Resolve Employer from Maps
    |--------------------------------------------------------------------------
    |
    | Used for staff-number preloading.
    |
    */

    private function resolveEmployerFromMaps(
        array $row,
        array $maps
    ): ?int {
        /*
        |--------------------------------------------------------------------------
        | PenAd First
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $row['penad_employer_number']
            )
        ) {
            $key =
                $this->employerReferenceKey(
                    $row['penad_employer_number']
                );

            if (
                isset(
                    $maps['penad'][$key]
                )
            ) {
                return
                    (int)
                    $maps['penad'][$key];
            }

            /*
            |--------------------------------------------------------------------------
            | PenAd supplied but incorrect means do not silently substitute.
            |--------------------------------------------------------------------------
            */

            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | PENERP Fallback
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $row['penerp_employer_number']
            )
        ) {
            $key =
                $this->employerReferenceKey(
                    $row['penerp_employer_number']
                );

            if (
                isset(
                    $maps['penerp'][$key]
                )
            ) {
                return
                    (int)
                    $maps['penerp'][$key];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Fundworx Fallback
        |--------------------------------------------------------------------------
        */

        if (
            !empty(
                $row['fundworx_employer_number']
            )
        ) {
            $key =
                $this->employerReferenceKey(
                    $row['fundworx_employer_number']
                );

            if (
                isset(
                    $maps['fundworx'][$key]
                )
            ) {
                return
                    (int)
                    $maps['fundworx'][$key];
            }
        }

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | Date
    |--------------------------------------------------------------------------
    */

    private function normalizeDate(
        mixed $value
    ): ?string {
        if (
            $value === null
            || $value === ''
        ) {
            return null;
        }

        try {
            if (
                is_numeric(
                    $value
                )
            ) {
                return
                    ExcelDate::excelToDateTimeObject(
                        (float) $value
                    )
                    ->format(
                        'Y-m-d'
                    );
            }

            $timestamp =
                strtotime(
                    (string) $value
                );

            if (
                $timestamp === false
            ) {
                return null;
            }

            return
                date(
                    'Y-m-d',
                    $timestamp
                );

        } catch (Throwable) {
            return null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Clean Text
    |--------------------------------------------------------------------------
    */

    private function cleanText(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    (string) $value
                )
            );

        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Generic Reference
    |--------------------------------------------------------------------------
    */

    private function cleanReference(
        mixed $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        if ($value === '') {
            return null;
        }

        $value =
            preg_replace(
                "/^'+/",
                '',
                $value
            );

        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Member Reference Key
    |--------------------------------------------------------------------------
    */

    private function memberReferenceKey(
        mixed $value
    ): string {
        return
            strtoupper(
                trim(
                    (string) $value
                )
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Blank Row
    |--------------------------------------------------------------------------
    */

    private function isBlankRow(
        array $row
    ): bool {
        foreach (
            $row
            as $value
        ) {
            if (
                $value !== null
                && trim(
                    (string) $value
                ) !== ''
            ) {
                return false;
            }
        }

        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Progress
    |--------------------------------------------------------------------------
    */

    private function updateProgress(
        MembershipImportBatch $batch,
        int $endRow,
        int $highestRow,
        int $processedRows,
        int $validRows = 0,
        int $warningRows = 0,
        int $errorRows = 0,
        int $duplicateRows = 0
    ): void {
        $scannedRows =
            max(
                0,
                $endRow - 1
            );

        $possibleRows =
            max(
                1,
                $highestRow - 1
            );

        $percentage =
            min(
                99,
                round(
                    (
                        $scannedRows
                        /
                        $possibleRows
                    )
                    * 100,
                    2
                )
            );

        $batch->update([
            'processed_rows' =>
                $processedRows,

            'valid_rows' =>
                $validRows,

            'warning_rows' =>
                $warningRows,

            'error_rows' =>
                $errorRows,

            'duplicate_rows' =>
                $duplicateRows,

            'progress_percentage' =>
                $percentage,
        ]);
    }
}