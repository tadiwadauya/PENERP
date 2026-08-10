<?php

namespace App\Services\PensionsAdministration\Updates;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\EmployerGroup;
use App\Models\PensionsAdministration\Updates\EmployerImportBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class EmployerImportValidationService
{
    private const CHUNK_SIZE = 500;

    private const EXPECTED_HEADERS = [
        'import_action',
        'penerp_employer_number',
        'penad_employer_number',
        'fundworx_employer_number',
        'employer_group_code',
        'employer_name',
        'short_name',
        'email',
        'telephone',
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
        'is_active',
    ];

    private array $seenPenerpNumbers = [];
    private array $seenPenadNumbers = [];
    private array $seenFundworxNumbers = [];
    private array $seenNames = [];

    public function process(EmployerImportBatch $batch): void
    {
        /*
        |--------------------------------------------------------------------------
        | Locate File
        |--------------------------------------------------------------------------
        */

        $disk = Storage::disk('local');

        if (!$disk->exists($batch->file_path)) {
            throw new RuntimeException(
                'The uploaded employer Excel file could not be found in storage. Stored path: '
                . $batch->file_path
            );
        }

        $path = $disk->path($batch->file_path);

        if (!is_file($path)) {
            throw new RuntimeException(
                'The employer Excel file exists in Laravel storage but could not be resolved to a physical file.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reset Existing Validation
        |--------------------------------------------------------------------------
        */

        $batch->rows()->delete();

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
            'imported_rows' => 0,

            'progress_percentage' => 0,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Workbook Information
        |--------------------------------------------------------------------------
        */

        $reader = IOFactory::createReaderForFile($path);

        $worksheetInfo = $reader->listWorksheetInfo($path);

        if (empty($worksheetInfo)) {
            throw new RuntimeException(
                'No worksheet was found in the employer Excel file.'
            );
        }

        $sheetInfo = collect($worksheetInfo)
            ->firstWhere('worksheetName', 'Employer Import Template')
            ?? $worksheetInfo[0];

        $sheetName = $sheetInfo['worksheetName'];
        $highestRow = (int) $sheetInfo['totalRows'];

        if ($highestRow < 2) {
            throw new RuntimeException(
                'The employer Excel file contains no employer records.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate Template
        |--------------------------------------------------------------------------
        */

        $this->validateHeaders(
            $path,
            $sheetName
        );

        /*
        |--------------------------------------------------------------------------
        | Lookup Maps
        |--------------------------------------------------------------------------
        */

        $employerGroups = $this->buildEmployerGroupMap();
        $existingEmployers = $this->buildEmployerMaps();

        /*
        |--------------------------------------------------------------------------
        | Counters
        |--------------------------------------------------------------------------
        */

        $totalRows = 0;
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
        | Process Excel in Chunks
        |--------------------------------------------------------------------------
        */

        for ($startRow = 2; $startRow <= $highestRow; $startRow += self::CHUNK_SIZE) {

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

            $insertRows = [];

            foreach ($candidates as $candidate) {

                $rowNumber = $candidate['row_number'];
                $raw = $candidate['raw'];

                if ($this->isBlankRow($raw)) {
                    continue;
                }

                $totalRows++;
                $processedRows++;

                $data = $this->normalizeRow($raw);

                $errors = [];
                $warnings = [];
                $duplicateReasons = [];

                $matchedEmployerGroupId = null;
                $matchedEmployerId = null;

                $duplicateStatus = 'none';
                $duplicateScore = null;

                /*
                |--------------------------------------------------------------------------
                | Import Action
                |--------------------------------------------------------------------------
                */

                $importAction = $data['import_action'];

                if (!in_array($importAction, ['AUTO', 'CREATE', 'UPDATE'], true)) {
                    $errors[] = 'Import Action must be AUTO, CREATE or UPDATE.';
                }

                /*
                |--------------------------------------------------------------------------
                | Employer Name
                |--------------------------------------------------------------------------
                */

                if (!$data['employer_name']) {
                    $errors[] = 'Employer name is required.';
                }

                /*
                |--------------------------------------------------------------------------
                | Employer Group
                |--------------------------------------------------------------------------
                */

                if (!$data['employer_group_code']) {
                    $errors[] = 'Employer group code is required.';
                } else {

                    $groupKey = $this->key(
                        $data['employer_group_code']
                    );

                    if (isset($employerGroups[$groupKey])) {
                        $matchedEmployerGroupId = $employerGroups[$groupKey];
                    } else {
                        $errors[] =
                            'Employer group code "'
                            . $data['employer_group_code']
                            . '" could not be found.';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Email
                |--------------------------------------------------------------------------
                */

                if (
                    $data['email']
                    && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)
                ) {
                    $errors[] = 'Employer email address is invalid.';
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Employer Matching
                |--------------------------------------------------------------------------
                */

                $matchIds = [];

                if ($data['penerp_employer_number']) {

                    $key = $this->key(
                        $data['penerp_employer_number']
                    );

                    if (isset($existingEmployers['penerp'][$key])) {
                        $matchIds[] = $existingEmployers['penerp'][$key];

                        $duplicateReasons[] =
                            'PENERP employer number already exists.';
                    }

                    if (isset($this->seenPenerpNumbers[$key])) {
                        $duplicateReasons[] =
                            'PENERP employer number is repeated in this file. First seen on row '
                            . $this->seenPenerpNumbers[$key]
                            . '.';
                    } else {
                        $this->seenPenerpNumbers[$key] = $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | PenAd Employer Number
                |--------------------------------------------------------------------------
                */

                if ($data['penad_employer_number']) {

                    $key = $this->key(
                        $data['penad_employer_number']
                    );

                    if (isset($existingEmployers['penad'][$key])) {
                        $matchIds[] = $existingEmployers['penad'][$key];

                        $duplicateReasons[] =
                            'PenAd employer number already exists.';
                    }

                    if (isset($this->seenPenadNumbers[$key])) {
                        $duplicateReasons[] =
                            'PenAd employer number is repeated in this file. First seen on row '
                            . $this->seenPenadNumbers[$key]
                            . '.';
                    } else {
                        $this->seenPenadNumbers[$key] = $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Fundworx Employer Number
                |--------------------------------------------------------------------------
                */

                if ($data['fundworx_employer_number']) {

                    $key = $this->key(
                        $data['fundworx_employer_number']
                    );

                    if (isset($existingEmployers['fundworx'][$key])) {
                        $matchIds[] = $existingEmployers['fundworx'][$key];

                        $duplicateReasons[] =
                            'Fundworx employer number already exists.';
                    }

                    if (isset($this->seenFundworxNumbers[$key])) {
                        $duplicateReasons[] =
                            'Fundworx employer number is repeated in this file. First seen on row '
                            . $this->seenFundworxNumbers[$key]
                            . '.';
                    } else {
                        $this->seenFundworxNumbers[$key] = $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Employer Name Matching
                |--------------------------------------------------------------------------
                */

                if ($data['employer_name_normalized']) {

                    $nameKey = $data['employer_name_normalized'];

                    if (isset($existingEmployers['name'][$nameKey])) {
                        $matchIds[] = $existingEmployers['name'][$nameKey];

                        $duplicateReasons[] =
                            'An employer with the same name already exists.';
                    }

                    if (isset($this->seenNames[$nameKey])) {
                        $duplicateReasons[] =
                            'Employer name is repeated in this file. First seen on row '
                            . $this->seenNames[$nameKey]
                            . '.';
                    } else {
                        $this->seenNames[$nameKey] = $rowNumber;
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve Matches
                |--------------------------------------------------------------------------
                */

                $matchIds = array_values(
                    array_unique(
                        array_filter($matchIds)
                    )
                );

                if (count($matchIds) > 1) {

                    $errors[] =
                        'The supplied employer references match different existing employers. Manual review is required.';

                } elseif (count($matchIds) === 1) {

                    $matchedEmployerId = $matchIds[0];
                    $duplicateStatus = 'exact';
                    $duplicateScore = 100;
                }

                /*
                |--------------------------------------------------------------------------
                | UPDATE Must Match Existing Employer
                |--------------------------------------------------------------------------
                */

                if (
                    $importAction === 'UPDATE'
                    && !$matchedEmployerId
                ) {
                    $errors[] =
                        'Import Action is UPDATE but no existing employer could be identified.';
                }

                /*
                |--------------------------------------------------------------------------
                | CREATE With Existing Employer
                |--------------------------------------------------------------------------
                */

                if (
                    $importAction === 'CREATE'
                    && $matchedEmployerId
                ) {
                    $warnings[] =
                        'Import Action is CREATE but an existing employer was found.';
                }

                /*
                |--------------------------------------------------------------------------
                | Duplicate Result
                |--------------------------------------------------------------------------
                */

                $duplicateReasons = array_values(
                    array_unique($duplicateReasons)
                );

                if ($duplicateReasons) {

                    $duplicateStatus = 'exact';
                    $duplicateScore = 100;

                    if (!$errors) {
                        $warnings[] =
                            'An existing or repeated employer reference requires review.';
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Validation Status
                |--------------------------------------------------------------------------
                */

                if ($errors) {

                    $validationStatus = 'error';
                    $errorRows++;

                } elseif ($warnings) {

                    $validationStatus = 'warning';
                    $warningRows++;

                } else {

                    $validationStatus = 'valid';
                    $validRows++;
                }

                if ($duplicateStatus !== 'none') {
                    $duplicateRows++;
                }

                /*
                |--------------------------------------------------------------------------
                | Stage Row
                |--------------------------------------------------------------------------
                */

                $insertRows[] = [
                    'import_batch_id' => $batch->id,
                    'row_number' => $rowNumber,
                    'import_action' => $importAction,

                    'raw_data' => json_encode(
                        $raw,
                        JSON_UNESCAPED_UNICODE
                    ),

                    'normalized_data' => json_encode(
                        $data,
                        JSON_UNESCAPED_UNICODE
                    ),

                    'validation_status' => $validationStatus,

                    'error_messages' => $errors
                        ? json_encode($errors, JSON_UNESCAPED_UNICODE)
                        : null,

                    'warning_messages' => $warnings
                        ? json_encode($warnings, JSON_UNESCAPED_UNICODE)
                        : null,

                    'matched_employer_group_id' => $matchedEmployerGroupId,
                    'matched_employer_id' => $matchedEmployerId,

                    'duplicate_status' => $duplicateStatus,
                    'duplicate_score' => $duplicateScore,

                    'duplicate_reasons' => $duplicateReasons
                        ? json_encode($duplicateReasons, JSON_UNESCAPED_UNICODE)
                        : null,

                    'review_decision' => 'pending',

                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | SQL Server Friendly Inserts
            |--------------------------------------------------------------------------
            */

            foreach (array_chunk($insertRows, 50) as $insertChunk) {
                DB::table('employer_import_rows')->insert($insertChunk);
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

            unset($candidates, $insertRows);

            gc_collect_cycles();
        }

        if ($totalRows === 0) {
            throw new RuntimeException(
                'No populated employer rows were found in the workbook.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Complete Validation
        |--------------------------------------------------------------------------
        */

        $batch->update([
            'total_rows' => $totalRows,
            'processed_rows' => $processedRows,
            'valid_rows' => $validRows,
            'warning_rows' => $warningRows,
            'error_rows' => $errorRows,
            'duplicate_rows' => $duplicateRows,

            'progress_percentage' => 100,
            'status' => 'awaiting_review',

            'completed_at' => now(),
        ]);
    }


    private function validateHeaders(
        string $path,
        string $sheetName
    ): void {
        $reader = IOFactory::createReaderForFile($path);

        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $reader->setReadFilter(
            new ChunkReadFilter(1, 1)
        );

        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (!$sheet) {
            throw new RuntimeException(
                'The Employer Import Template worksheet could not be opened.'
            );
        }

        $row = $sheet->rangeToArray(
            'A1:U1',
            null,
            false,
            false
        )[0];

        $headers = array_map(
            fn ($value) => strtolower(
                trim((string) $value)
            ),
            $row
        );

        if ($headers !== self::EXPECTED_HEADERS) {

            $errors = [];

            foreach (self::EXPECTED_HEADERS as $index => $expected) {

                $actual = $headers[$index] ?? '[missing]';

                if ($actual !== $expected) {
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

            $spreadsheet->disconnectWorksheets();

            throw new RuntimeException(
                'Invalid employer import template. '
                . implode(
                    ' ',
                    array_slice($errors, 0, 5)
                )
            );
        }

        $spreadsheet->disconnectWorksheets();
    }


    private function readChunk(
        string $path,
        string $sheetName,
        int $startRow,
        int $endRow
    ): array {
        $reader = IOFactory::createReaderForFile($path);

        $reader->setReadDataOnly(true);
        $reader->setLoadSheetsOnly([$sheetName]);

        $reader->setReadFilter(
            new ChunkReadFilter(
                $startRow,
                self::CHUNK_SIZE
            )
        );

        $spreadsheet = $reader->load($path);

        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (!$sheet) {
            $spreadsheet->disconnectWorksheets();

            return [];
        }

        $rows = $sheet->rangeToArray(
            'A' . $startRow . ':U' . $endRow,
            null,
            false,
            false
        );

        $result = [];

        foreach ($rows as $offset => $values) {

            $values = array_pad(
                array_slice(
                    $values,
                    0,
                    count(self::EXPECTED_HEADERS)
                ),
                count(self::EXPECTED_HEADERS),
                null
            );

            $result[] = [
                'row_number' => $startRow + $offset,

                'raw' => array_combine(
                    self::EXPECTED_HEADERS,
                    $values
                ),
            ];
        }

        $spreadsheet->disconnectWorksheets();

        unset($spreadsheet);

        return $result;
    }


    private function normalizeRow(array $row): array
    {
        $data = [];

        foreach ($row as $key => $value) {

            if (is_string($value)) {
                $value = trim($value);
            }

            $data[$key] = $value === ''
                ? null
                : $value;
        }

        /*
        |--------------------------------------------------------------------------
        | References
        |--------------------------------------------------------------------------
        */

        foreach ([
            'penerp_employer_number',
            'penad_employer_number',
            'fundworx_employer_number',
            'employer_group_code',
        ] as $field) {
            $data[$field] = $this->cleanReference(
                $data[$field] ?? null
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Import Action
        |--------------------------------------------------------------------------
        */

        $data['import_action'] = strtoupper(
            trim(
                (string) ($data['import_action'] ?? 'AUTO')
            )
        );

        /*
        |--------------------------------------------------------------------------
        | Employer Name
        |--------------------------------------------------------------------------
        */

        $data['employer_name'] = $this->cleanText(
            $data['employer_name'] ?? null
        );

        $data['employer_name_normalized'] =
            $this->normalizeName(
                $data['employer_name']
            );

        $data['short_name'] = $this->cleanText(
            $data['short_name'] ?? null
        );

        /*
        |--------------------------------------------------------------------------
        | Email
        |--------------------------------------------------------------------------
        */

        $data['email'] = $data['email']
            ? strtolower(trim((string) $data['email']))
            : null;

        /*
        |--------------------------------------------------------------------------
        | Active Status
        |--------------------------------------------------------------------------
        */

        $data['is_active'] = $this->normalizeBoolean(
            $data['is_active'] ?? null
        );

        return $data;
    }


    private function buildEmployerGroupMap(): array
    {
        $groups = EmployerGroup::query()
            ->get([
                'id',
                'code',
            ]);

        $map = [];

        foreach ($groups as $group) {

            if (!$group->code) {
                continue;
            }

            $map[
                $this->key($group->code)
            ] = $group->id;
        }

        return $map;
    }


    private function buildEmployerMaps(): array
    {
        $employers = Employer::query()
            ->get([
                'id',
                'employer_number',
                'penad_employer_number',
                'fundworx_employer_number',
                'name',
            ]);

        $maps = [
            'penerp' => [],
            'penad' => [],
            'fundworx' => [],
            'name' => [],
        ];

        foreach ($employers as $employer) {

            if ($employer->employer_number) {

                $maps['penerp'][
                    $this->key(
                        $employer->employer_number
                    )
                ] = $employer->id;
            }

            if ($employer->penad_employer_number) {

                $maps['penad'][
                    $this->key(
                        $employer->penad_employer_number
                    )
                ] = $employer->id;
            }

            if ($employer->fundworx_employer_number) {

                $maps['fundworx'][
                    $this->key(
                        $employer->fundworx_employer_number
                    )
                ] = $employer->id;
            }

            if ($employer->name) {

                $maps['name'][
                    $this->normalizeName(
                        $employer->name
                    )
                ] = $employer->id;
            }
        }

        return $maps;
    }


    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtoupper(
            trim(
                (string) $value
            )
        );

        return in_array(
            $value,
            [
                '1',
                'YES',
                'Y',
                'TRUE',
                'ACTIVE',
            ],
            true
        );
    }


    private function normalizeName(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = strtoupper($value);

        $value = preg_replace(
            '/[^A-Z0-9]/',
            '',
            $value
        );

        return $value ?: null;
    }


    private function cleanText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace(
            '/\s+/',
            ' ',
            (string) $value
        );

        $value = trim($value);

        return $value !== ''
            ? $value
            : null;
    }


    private function cleanReference(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(
            (string) $value
        );

        return $value !== ''
            ? $value
            : null;
    }


    private function key(mixed $value): string
    {
        return strtoupper(
            trim(
                (string) $value
            )
        );
    }


    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {

            if (
                $value !== null
                && trim((string) $value) !== ''
            ) {
                return false;
            }
        }

        return true;
    }


    private function updateProgress(
        EmployerImportBatch $batch,
        int $endRow,
        int $highestRow,
        int $processedRows,
        int $validRows,
        int $warningRows,
        int $errorRows,
        int $duplicateRows
    ): void {
        $scannedRows = max(
            0,
            $endRow - 1
        );

        $possibleRows = max(
            1,
            $highestRow - 1
        );

        $percentage = min(
            99,
            round(
                ($scannedRows / $possibleRows) * 100,
                2
            )
        );

        $batch->update([
            'processed_rows' => $processedRows,
            'valid_rows' => $validRows,
            'warning_rows' => $warningRows,
            'error_rows' => $errorRows,
            'duplicate_rows' => $duplicateRows,
            'progress_percentage' => $percentage,
        ]);
    }
}