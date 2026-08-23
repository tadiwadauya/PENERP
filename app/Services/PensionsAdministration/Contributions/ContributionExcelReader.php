<?php

namespace App\Services\PensionsAdministration\Contributions;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class ContributionExcelReader
{
    /*
    |--------------------------------------------------------------------------
    | Column Aliases
    |--------------------------------------------------------------------------
    |
    | The first value in each alias list is the preferred PENERP template
    | heading.
    |
    | The contribution upload supports:
    |
    | 1. New generic multi-currency template
    | 2. Historical ZWG-specific schedules
    | 3. Historical USD-specific schedules
    |
    | Currency selection is performed at batch upload level.
    |
    */

    private array $aliases = [

        /*
        |--------------------------------------------------------------------------
        | Employer / Scheme / Period
        |--------------------------------------------------------------------------
        */

        'employer_number' => [
            'employer_number',
            'employer number',
            'employer no',
            'employer no.',
            'employer code',
        ],

        'scheme_code' => [
            'scheme_code',
            'scheme code',
            'scheme',
            'fund code',
        ],

        'due_date' => [
            'due_date',
            'due date',
            'contribution date',
            'period date',
        ],


        /*
        |--------------------------------------------------------------------------
        | Member Numbers
        |--------------------------------------------------------------------------
        */

        'penad_member_number' => [
            'penad_member_number',
            'penad member number',
            'penad number',
            'penad no',
        ],

        'penerp_member_number' => [
            'penerp_member_number',
            'penerp member number',
            'penerp number',
            'penerp no',
        ],

        'fundworx_member_number' => [
            'fundworx_member_number',
            'fundworx member number',
            'fundworx number',
            'fundworx no',
        ],

        'pension_reference_number' => [
            'pension_reference_number',
            'pension reference number',
            'pension reference',
            'member number',
            'member no',
            'member no.',
        ],


        /*
        |--------------------------------------------------------------------------
        | Member Identification
        |--------------------------------------------------------------------------
        */

        'staff_number' => [
            'staff_number',
            'staff number',
            'staff no',
            'staff no.',
            'employee number',
            'employee no',
            'employee no.',
            'employee code',
            'employee code or works number',
            'works number',
            'works no',
            'works no.',
        ],

        'national_id' => [
            'national_id',
            'national id',
            'national id no',
            'national id number',
            'national registration number',
            'national registration no',
            'id number',
            'id no',
            'nationalidno',
        ],


        /*
        |--------------------------------------------------------------------------
        | Member Names
        |--------------------------------------------------------------------------
        */

        'surname' => [
            'surname',
            'last name',
            'lastname',
        ],

        'first_names' => [
            'first_names',
            'first names',
            'first name',
            'firstname',
            'forename',
            'forenames',
        ],

        'other_names' => [
            'other_names',
            'other names',
            'other name',
            'middle names',
            'middle name',
        ],


        /*
        |--------------------------------------------------------------------------
        | Member Personal Details
        |--------------------------------------------------------------------------
        */

        'date_of_birth' => [
            'date_of_birth',
            'date of birth',
            'dob',
            'birth date',
        ],

        'gender' => [
            'gender',
            'sex',
        ],

        'marital_status' => [
            'marital_status',
            'marital status',
            'maritalstatus',
        ],

        'cellphone_number' => [
            'cellphone_number',
            'cellphone number',
            'cell phone number',
            'cellphonenumber',
            'mobile_number',
            'mobile number',
            'mobile',
            'cell',
            'mobile phone',
            'mobile phone number',
        ],

        'email_address' => [
            'email_address',
            'email address',
            'emailaddress',
            'email',
        ],

        'home_address' => [
            'home_address',
            'home address',
            'homeaddress',
            'residential_address',
            'residential address',
            'residential',
        ],


        /*
        |--------------------------------------------------------------------------
        | Membership / Employment Dates
        |--------------------------------------------------------------------------
        */

        'date_joined_fund' => [
            'date_joined_fund',
            'date joined fund',
            'fund join date',
            'date joined scheme',
            'scheme join date',
        ],

        'date_joined_employer' => [
            'date_joined_employer',
            'date joined employer',
            'employment date',
            'date employed',
            'date joined employment',
        ],


        /*
        |--------------------------------------------------------------------------
        | Employment Details
        |--------------------------------------------------------------------------
        */

        'occupation' => [
            'occupation',
            'job title',
        ],

        'branch' => [
            'branch',
        ],

        'department' => [
            'department',
        ],

        'payment_flag' => [
            'payment_flag',
            'payment flag',
            'payment status',
        ],


        /*
        |--------------------------------------------------------------------------
        | Generic Multi-Currency Financial Columns
        |--------------------------------------------------------------------------
        |
        | These are the preferred PENERP template columns.
        |
        | The selected batch currency determines whether these values are
        | mapped to ZWG or USD by ContributionImportValidator.
        |
        */

        'basic_pay' => [
            'basic_pay',
            'basic pay',
            'pensionable salary',
            'salary',
        ],

        'employee_rate' => [
            'employee_rate',
            'employee rate',
            'member rate',
        ],

        'employer_rate' => [
            'employer_rate',
            'employer rate',
        ],

        'employee_contribution' => [
            'employee_contribution',
            'employee contribution',
            'member contribution',
        ],

        'employer_contribution' => [
            'employer_contribution',
            'employer contribution',
        ],

        'employee_avc' => [
            'employee_avc',
            'employee avc',
            'employee voluntary contribution',
            'member avc',
        ],

        'employer_avc' => [
            'employer_avc',
            'employer avc',
            'employer voluntary contribution',
        ],

        'employee_arrear' => [
            'employee_arrear',
            'employee arrear',
            'employee arrear contribution',
            'member arrear contribution',
        ],

        'employer_arrear' => [
            'employer_arrear',
            'employer arrear',
            'employer arrear contribution',
        ],

        'employee_transfer_in' => [
            'employee_transfer_in',
            'employee transfer in',
            'member transfer in',
        ],

        'employer_transfer_in' => [
            'employer_transfer_in',
            'employer transfer in',
        ],

        'employee_late_interest' => [
            'employee_late_interest',
            'employee late interest',
            'employee late payment interest',
        ],

        'employer_late_interest' => [
            'employer_late_interest',
            'employer late interest',
            'employer late payment interest',
        ],


        /*
        |--------------------------------------------------------------------------
        | Legacy USD Financial Columns
        |--------------------------------------------------------------------------
        */

        'usd_basic_pay' => [
            'usd_basic_pay',
            'usd basic pay',
            'usd salary',
            'usd pensionable salary',
        ],

        'usd_employee_rate' => [
            'usd_employee_rate',
            'usd employee rate',
            'usd member rate',
        ],

        'usd_employer_rate' => [
            'usd_employer_rate',
            'usd employer rate',
        ],

        'usd_employee_contribution' => [
            'usd_employee_contribution',
            'usd employee contribution',
            'usd member contribution',
        ],

        'usd_employer_contribution' => [
            'usd_employer_contribution',
            'usd employer contribution',
        ],

        'usd_employee_avc' => [
            'usd_employee_avc',
            'usd employee avc',
            'usd employee voluntary contribution',
            'usd member avc',
        ],

        'usd_employer_avc' => [
            'usd_employer_avc',
            'usd employer avc',
            'usd employer voluntary contribution',
        ],

        'usd_employee_arrear' => [
            'usd_employee_arrear',
            'usd employee arrear',
            'usd employee arrear contribution',
        ],

        'usd_employer_arrear' => [
            'usd_employer_arrear',
            'usd employer arrear',
            'usd employer arrear contribution',
        ],

        'usd_employee_transfer_in' => [
            'usd_employee_transfer_in',
            'usd employee transfer in',
        ],

        'usd_employer_transfer_in' => [
            'usd_employer_transfer_in',
            'usd employer transfer in',
        ],

        'usd_employee_late_interest' => [
            'usd_employee_late_interest',
            'usd employee late interest',
            'usd employee late payment interest',
        ],

        'usd_employer_late_interest' => [
            'usd_employer_late_interest',
            'usd employer late interest',
            'usd employer late payment interest',
        ],


        /*
        |--------------------------------------------------------------------------
        | Legacy ZWG Financial Columns
        |--------------------------------------------------------------------------
        */

        'zwg_basic_pay' => [
            'zwg_basic_pay',
            'zwg basic pay',
            'zwg salary',
            'zwg pensionable salary',
        ],

        'zwg_employee_rate' => [
            'zwg_employee_rate',
            'zwg employee rate',
            'zwg member rate',
        ],

        'zwg_employer_rate' => [
            'zwg_employer_rate',
            'zwg employer rate',
        ],

        'zwg_employee_contribution' => [
            'zwg_employee_contribution',
            'zwg employee contribution',
            'zwg member contribution',
        ],

        'zwg_employer_contribution' => [
            'zwg_employer_contribution',
            'zwg employer contribution',
        ],

        'zwg_employee_avc' => [
            'zwg_employee_avc',
            'zwg employee avc',
            'zwg employee voluntary contribution',
            'zwg member avc',
        ],

        'zwg_employer_avc' => [
            'zwg_employer_avc',
            'zwg employer avc',
            'zwg employer voluntary contribution',
        ],

        'zwg_employee_arrear' => [
            'zwg_employee_arrear',
            'zwg employee arrear',
            'zwg employee arrear contribution',
        ],

        'zwg_employer_arrear' => [
            'zwg_employer_arrear',
            'zwg employer arrear',
            'zwg employer arrear contribution',
        ],

        'zwg_employee_transfer_in' => [
            'zwg_employee_transfer_in',
            'zwg employee transfer in',
        ],

        'zwg_employer_transfer_in' => [
            'zwg_employer_transfer_in',
            'zwg employer transfer in',
        ],

        'zwg_employee_late_interest' => [
            'zwg_employee_late_interest',
            'zwg employee late interest',
            'zwg employee late payment interest',
        ],

        'zwg_employer_late_interest' => [
            'zwg_employer_late_interest',
            'zwg employer late interest',
            'zwg employer late payment interest',
        ],


        /*
        |--------------------------------------------------------------------------
        | Comments
        |--------------------------------------------------------------------------
        */

        'comments' => [
            'comments',
            'comment',
            'remarks',
            'remark',
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Required New PENERP Template Columns
    |--------------------------------------------------------------------------
    |
    | These columns must EXIST in the Excel file.
    |
    | Important:
    |
    | Some fields such as DOB, National ID, Marital Status, Cell Phone,
    | Email and Home Address may be blank for an EXISTING member.
    |
    | ContributionImportValidator determines whether their values are required
    | based on whether the row is an existing member or a proposed new member.
    |
    */

    private array $requiredFields = [
        'employer_number',
        'scheme_code',
        'due_date',

        'surname',
        'first_names',
        'staff_number',

        'national_id',
        'date_of_birth',
        'date_joined_fund',
        'date_joined_employer',
        'gender',
        'marital_status',
        'cellphone_number',
        'email_address',
        'home_address',

        'basic_pay',
        'employee_rate',
        'employer_rate',
        'employee_contribution',
        'employer_contribution',
    ];


    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | Contribution workbooks can be large. PhpSpreadsheet creates a sizeable
    | in-memory object for every loaded cell, therefore PENERP must never load
    | the complete workbook during contribution validation.
    |
    | 250 rows is deliberately conservative because contribution schedules
    | contain considerably more columns than the static membership template.
    |
    */

    private const CHUNK_SIZE = 250;


    /*
    |--------------------------------------------------------------------------
    | Inspect Excel File
    |--------------------------------------------------------------------------
    |
    | Reads workbook metadata and the heading row only. No contribution data
    | rows are retained in memory here.
    |
    */

    public function inspect(
        string $path
    ): array {
        if (!file_exists($path)) {
            throw new RuntimeException(
                'The contribution Excel file could not be found.'
            );
        }

        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);

            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }

            $worksheetInfo = $reader->listWorksheetInfo($path);

            if (empty($worksheetInfo)) {
                throw new RuntimeException(
                    'The contribution workbook does not contain any worksheets.'
                );
            }

            $info = $worksheetInfo[0];

            $sheetName = $info['worksheetName'] ?? null;
            $highestRow = (int) ($info['totalRows'] ?? 0);
            $highestColumn = $info['lastColumnLetter'] ?? null;

            if (!$sheetName) {
                throw new RuntimeException(
                    'The contribution worksheet name could not be determined.'
                );
            }

            if (!$highestColumn || $highestRow < 2) {
                throw new RuntimeException(
                    'The contribution Excel file does not contain any contribution rows.'
                );
            }

            /*
            |------------------------------------------------------------------
            | Load Header Row Only
            |------------------------------------------------------------------
            */

            $headerReader = IOFactory::createReaderForFile($path);
            $headerReader->setReadDataOnly(true);
            $headerReader->setLoadSheetsOnly([$sheetName]);

            if (method_exists($headerReader, 'setReadEmptyCells')) {
                $headerReader->setReadEmptyCells(false);
            }

            $headerReader->setReadFilter(
                $this->makeChunkReadFilter(1, 1)
            );

            $spreadsheet = $headerReader->load($path);
            $sheet = $spreadsheet->getSheetByName($sheetName);

            if (!$sheet) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $headerReader, $reader);
                gc_collect_cycles();

                throw new RuntimeException(
                    'The contribution worksheet could not be opened.'
                );
            }

            $headerRow = $sheet->rangeToArray(
                'A1:' . $highestColumn . '1',
                null,
                true,
                false
            )[0];

            $columnMap = $this->buildColumnMap($headerRow);

            $this->validateRequiredColumns($columnMap);

            $spreadsheet->disconnectWorksheets();

            unset(
                $sheet,
                $spreadsheet,
                $headerReader,
                $reader,
                $worksheetInfo
            );

            gc_collect_cycles();

            return [
                'sheet_name' => $sheetName,
                'highest_row' => $highestRow,
                'highest_column' => $highestColumn,
                'headers' => $headerRow,
                'column_map' => $columnMap,
                'estimated_rows' => max(0, $highestRow - 1),
            ];

        } catch (Throwable $e) {
            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException(
                'The contribution Excel file could not be inspected: '
                . $e->getMessage(),
                previous: $e
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Stream Contribution Rows
    |--------------------------------------------------------------------------
    |
    | Yields one normalized row at a time while the workbook is loaded in small
    | chunks. The caller therefore never receives one giant array containing
    | the complete contribution schedule.
    |
    */

    public function rows(
        string $path,
        array $metadata
    ): \Generator {
        if (!file_exists($path)) {
            throw new RuntimeException(
                'The contribution Excel file could not be found.'
            );
        }

        $sheetName = (string) ($metadata['sheet_name'] ?? '');
        $highestRow = (int) ($metadata['highest_row'] ?? 0);
        $highestColumn = (string) ($metadata['highest_column'] ?? '');
        $headerRow = $metadata['headers'] ?? [];
        $columnMap = $metadata['column_map'] ?? [];

        if (
            $sheetName === ''
            || $highestRow < 2
            || $highestColumn === ''
            || empty($headerRow)
            || empty($columnMap)
        ) {
            throw new RuntimeException(
                'Contribution workbook metadata is incomplete. The file must be inspected before its rows are read.'
            );
        }

        for (
            $startRow = 2;
            $startRow <= $highestRow;
            $startRow += self::CHUNK_SIZE
        ) {
            $endRow = min(
                $highestRow,
                $startRow + self::CHUNK_SIZE - 1
            );

            $reader = null;
            $spreadsheet = null;
            $sheet = null;
            $chunkRows = null;

            try {
                $reader = IOFactory::createReaderForFile($path);
                $reader->setReadDataOnly(true);
                $reader->setLoadSheetsOnly([$sheetName]);

                if (method_exists($reader, 'setReadEmptyCells')) {
                    $reader->setReadEmptyCells(false);
                }

                $reader->setReadFilter(
                    $this->makeChunkReadFilter(
                        $startRow,
                        $endRow
                    )
                );

                $spreadsheet = $reader->load($path);
                $sheet = $spreadsheet->getSheetByName($sheetName);

                if (!$sheet) {
                    throw new RuntimeException(
                        'The contribution worksheet could not be opened while reading rows.'
                    );
                }

                $chunkRows = $sheet->rangeToArray(
                    'A' . $startRow . ':' . $highestColumn . $endRow,
                    null,
                    true,
                    false
                );

                foreach ($chunkRows as $offset => $values) {
                    $rowNumber = $startRow + $offset;

                    if ($this->isEmptyRow($values)) {
                        continue;
                    }

                    if ($this->looksLikeTotalRow($values)) {
                        continue;
                    }

                    $rawData = [];

                    foreach ($headerRow as $columnIndex => $heading) {
                        $heading = trim((string) $heading);

                        if ($heading === '') {
                            continue;
                        }

                        $rawData[$heading] = $values[$columnIndex] ?? null;
                    }

                    $normalizedData = $this->normalizeRow(
                        $values,
                        $columnMap
                    );

                    yield [
                        'row_number' => $rowNumber,
                        'raw_data' => $rawData,
                        'normalized_data' => $normalizedData,
                    ];

                    unset($rawData, $normalizedData, $values);
                }

            } catch (Throwable $e) {
                if ($e instanceof RuntimeException) {
                    throw $e;
                }

                throw new RuntimeException(
                    'The contribution Excel file could not be read around rows '
                    . $startRow
                    . ' to '
                    . $endRow
                    . ': '
                    . $e->getMessage(),
                    previous: $e
                );

            } finally {
                if ($spreadsheet) {
                    $spreadsheet->disconnectWorksheets();
                }

                unset(
                    $chunkRows,
                    $sheet,
                    $spreadsheet,
                    $reader
                );

                gc_collect_cycles();
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Chunk Read Filter
    |--------------------------------------------------------------------------
    |
    | Kept inside this class so no additional service file is required.
    |
    */

    private function makeChunkReadFilter(
        int $startRow,
        int $endRow
    ): IReadFilter {
        return new class(
            $startRow,
            $endRow
        ) implements IReadFilter {
            public function __construct(
                private readonly int $startRow,
                private readonly int $endRow
            ) {
            }

            public function readCell(
                string $columnAddress,
                int $row,
                string $worksheetName = ''
            ): bool {
                if ($row === 1) {
                    return true;
                }

                return $row >= $this->startRow
                    && $row <= $this->endRow;
            }
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Build Column Map
    |--------------------------------------------------------------------------
    */

    private function buildColumnMap(
        array $headers
    ): array {
        $normalizedHeaders =
            [];


        /*
        |--------------------------------------------------------------------------
        | Normalize Excel Headers
        |--------------------------------------------------------------------------
        */

        foreach (
            $headers
            as $index => $header
        ) {
            $normalizedHeaders[
                $index
            ] =
                $this->normalizeHeading(
                    $header
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Match Logical Fields To Columns
        |--------------------------------------------------------------------------
        |
        | Alias priority is important.
        |
        | Therefore:
        |
        | - Preferred alias is checked first.
        | - Older aliases are checked afterwards.
        |
        */

        $map =
            [];


        foreach (
            $this->aliases
            as $logicalField => $aliases
        ) {
            foreach (
                $aliases
                as $alias
            ) {
                $normalizedAlias =
                    $this->normalizeHeading(
                        $alias
                    );


                foreach (
                    $normalizedHeaders
                    as $index => $normalizedHeading
                ) {
                    if (
                        $normalizedHeading
                        ===
                        $normalizedAlias
                    ) {
                        $map[
                            $logicalField
                        ] =
                            $index;

                        break 2;
                    }
                }
            }
        }


        return $map;
    }


    /*
    |--------------------------------------------------------------------------
    | Required Columns
    |--------------------------------------------------------------------------
    */

    private function validateRequiredColumns(
        array $columnMap
    ): void {
        $missing =
            [];


        foreach (
            $this->requiredFields
            as $requiredField
        ) {
            if (
                !array_key_exists(
                    $requiredField,
                    $columnMap
                )
            ) {
                $missing[] =
                    $requiredField;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Generic Financial Template
        |--------------------------------------------------------------------------
        |
        | The preferred template contains:
        |
        | basic_pay
        | employee_rate
        | employer_rate
        | employee_contribution
        | employer_contribution
        |
        | However, legacy ZWG/USD schedules may have currency-specific financial
        | headings instead.
        |
        | The compatibility check below allows those old schedules.
        |
        */

        $missing =
            array_values(
                array_filter(
                    $missing,
                    function (
                        string $field
                    ) use (
                        $columnMap
                    ): bool {

                        return !$this->hasLegacyFinancialAlternative(
                            $field,
                            $columnMap
                        );
                    }
                )
            );


        if (
            !empty(
                $missing
            )
        ) {
            throw new RuntimeException(
                'Required contribution column(s) could not be identified: '
                . implode(
                    ', ',
                    $missing
                )
                . '. Please use the current PENERP Monthly Contributions Upload Template.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Legacy Financial Alternative
    |--------------------------------------------------------------------------
    */

    private function hasLegacyFinancialAlternative(
        string $field,
        array $columnMap
    ): bool {
        $legacyFields = [
            'basic_pay',
            'employee_rate',
            'employer_rate',
            'employee_contribution',
            'employer_contribution',
        ];


        if (
            !in_array(
                $field,
                $legacyFields,
                true
            )
        ) {
            return false;
        }


        return
            array_key_exists(
                'zwg_'
                . $field,
                $columnMap
            )
            ||
            array_key_exists(
                'usd_'
                . $field,
                $columnMap
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Contribution Row
    |--------------------------------------------------------------------------
    */

    private function normalizeRow(
        array $values,
        array $map
    ): array {
        return [

            /*
            |--------------------------------------------------------------------------
            | Employer / Period
            |--------------------------------------------------------------------------
            */

            'employer_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_number'
                    )
                ),

            'scheme_code' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'scheme_code'
                    )
                ),

            'due_date' =>
                $this->dateValue(
                    $this->value(
                        $values,
                        $map,
                        'due_date'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Member Numbers
            |--------------------------------------------------------------------------
            */

            'penad_member_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'penad_member_number'
                    )
                ),

            'penerp_member_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'penerp_member_number'
                    )
                ),

            'fundworx_member_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'fundworx_member_number'
                    )
                ),

            'pension_reference_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'pension_reference_number'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Member Identification
            |--------------------------------------------------------------------------
            */

            'staff_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'staff_number'
                    )
                ),

            'national_id' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'national_id'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Member Names
            |--------------------------------------------------------------------------
            */

            'surname' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'surname'
                    )
                ),

            'first_names' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'first_names'
                    )
                ),

            'other_names' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'other_names'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Personal Details
            |--------------------------------------------------------------------------
            */

            'date_of_birth' =>
                $this->dateValue(
                    $this->value(
                        $values,
                        $map,
                        'date_of_birth'
                    )
                ),

            'gender' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'gender'
                    )
                ),

            'marital_status' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'marital_status'
                    )
                ),

            'cellphone_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'cellphone_number'
                    )
                ),

            'email_address' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'email_address'
                    )
                ),

            'home_address' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'home_address'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Membership / Employment Dates
            |--------------------------------------------------------------------------
            */

            'date_joined_fund' =>
                $this->dateValue(
                    $this->value(
                        $values,
                        $map,
                        'date_joined_fund'
                    )
                ),

            'date_joined_employer' =>
                $this->dateValue(
                    $this->value(
                        $values,
                        $map,
                        'date_joined_employer'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Employment Details
            |--------------------------------------------------------------------------
            */

            'occupation' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'occupation'
                    )
                ),

            'branch' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'branch'
                    )
                ),

            'department' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'department'
                    )
                ),

            'payment_flag' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'payment_flag'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Generic Financial Values
            |--------------------------------------------------------------------------
            */

            'basic_pay' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'basic_pay'
                    )
                ),

            'employee_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_rate'
                    )
                ),

            'employer_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_rate'
                    )
                ),

            'employee_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_contribution'
                    )
                ),

            'employer_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_contribution'
                    )
                ),

            'employee_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_avc'
                    )
                ),

            'employer_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_avc'
                    )
                ),

            'employee_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_arrear'
                    )
                ),

            'employer_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_arrear'
                    )
                ),

            'employee_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_transfer_in'
                    )
                ),

            'employer_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_transfer_in'
                    )
                ),

            'employee_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_late_interest'
                    )
                ),

            'employer_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employer_late_interest'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Legacy USD Values
            |--------------------------------------------------------------------------
            */

            'usd_basic_pay' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_basic_pay'
                    )
                ),

            'usd_employee_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_rate'
                    )
                ),

            'usd_employer_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_rate'
                    )
                ),

            'usd_employee_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_contribution'
                    )
                ),

            'usd_employer_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_contribution'
                    )
                ),

            'usd_employee_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_avc'
                    )
                ),

            'usd_employer_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_avc'
                    )
                ),

            'usd_employee_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_arrear'
                    )
                ),

            'usd_employer_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_arrear'
                    )
                ),

            'usd_employee_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_transfer_in'
                    )
                ),

            'usd_employer_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_transfer_in'
                    )
                ),

            'usd_employee_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_late_interest'
                    )
                ),

            'usd_employer_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employer_late_interest'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Legacy ZWG Values
            |--------------------------------------------------------------------------
            */

            'zwg_basic_pay' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_basic_pay'
                    )
                ),

            'zwg_employee_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_rate'
                    )
                ),

            'zwg_employer_rate' =>
                $this->rateValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_rate'
                    )
                ),

            'zwg_employee_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_contribution'
                    )
                ),

            'zwg_employer_contribution' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_contribution'
                    )
                ),

            'zwg_employee_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_avc'
                    )
                ),

            'zwg_employer_avc' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_avc'
                    )
                ),

            'zwg_employee_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_arrear'
                    )
                ),

            'zwg_employer_arrear' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_arrear'
                    )
                ),

            'zwg_employee_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_transfer_in'
                    )
                ),

            'zwg_employer_transfer_in' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_transfer_in'
                    )
                ),

            'zwg_employee_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_late_interest'
                    )
                ),

            'zwg_employer_late_interest' =>
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employer_late_interest'
                    )
                ),


            /*
            |--------------------------------------------------------------------------
            | Comments
            |--------------------------------------------------------------------------
            */

            'comments' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'comments'
                    )
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Get Cell Value
    |--------------------------------------------------------------------------
    */

    private function value(
        array $values,
        array $map,
        string $field
    ): mixed {
        if (
            !array_key_exists(
                $field,
                $map
            )
        ) {
            return null;
        }


        return $values[
            $map[
                $field
            ]
        ]
            ??
            null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Heading
    |--------------------------------------------------------------------------
    |
    | Examples:
    |
    | employee_rate
    | Employee Rate
    | employee-rate
    |
    | all become:
    |
    | employee rate
    |
    */

    private function normalizeHeading(
        mixed $value
    ): string {
        $value =
            strtolower(
                trim(
                    (string)
                    $value
                )
            );


        /*
        |--------------------------------------------------------------------------
        | Replace Underscores / Hyphens
        |--------------------------------------------------------------------------
        */

        $value =
            str_replace(
                [
                    '_',
                    '-',
                ],
                ' ',
                $value
            );


        /*
        |--------------------------------------------------------------------------
        | Remove Repeated Whitespace
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                '/\s+/',
                ' ',
                $value
            )
            ??
            $value;


        return trim(
            $value
        );
    }


    /*
    |--------------------------------------------------------------------------
    | String Value
    |--------------------------------------------------------------------------
    */

    private function stringValue(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }


        $value =
            trim(
                (string)
                $value
            );


        return
            $value !== ''
                ? $value
                : null;
    }


    /*
    |--------------------------------------------------------------------------
    | Number Value
    |--------------------------------------------------------------------------
    */

    private function numberValue(
        mixed $value
    ): float {
        if (
            $value === null
        ) {
            return 0.0;
        }


        if (
            is_numeric(
                $value
            )
        ) {
            return (float)
                $value;
        }


        $clean =
            trim(
                (string)
                $value
            );


        if (
            $clean === ''
        ) {
            return 0.0;
        }


        /*
        |--------------------------------------------------------------------------
        | Accounting Negative
        |--------------------------------------------------------------------------
        |
        | (1,000.00) => -1000.00
        |
        */

        $negative =
            str_starts_with(
                $clean,
                '('
            )
            &&
            str_ends_with(
                $clean,
                ')'
            );


        $clean =
            str_ireplace(
                [
                    ',',
                    '$',
                    'ZWG',
                    'USD',
                    ' ',
                    '(',
                    ')',
                ],
                '',
                $clean
            );


        if ($negative) {
            $clean =
                '-'
                . $clean;
        }


        if (
            !is_numeric(
                $clean
            )
        ) {
            return 0.0;
        }


        return (float)
            $clean;
    }


    /*
    |--------------------------------------------------------------------------
    | Contribution Rate
    |--------------------------------------------------------------------------
    |
    | Normalises rates into percentage points.
    |
    | Examples:
    |
    | Excel value     Result
    | -----------     ------
    | 6               6.00
    | 6%              6.00
    | 0.06            6.00
    | 17.3            17.30
    | 17.3%           17.30
    | 0.173           17.30
    |
    | This means ContributionImportValidator can always compare against:
    |
    | Employee: 5.00 - 6.00
    | New member: 6.00
    | Employer: 17.30
    |
    */

    private function rateValue(
        mixed $value
    ): float {
        if (
            $value === null
        ) {
            return 0.0;
        }


        /*
        |--------------------------------------------------------------------------
        | Numeric Excel Percentage
        |--------------------------------------------------------------------------
        |
        | An Excel cell formatted as 6% is normally returned as 0.06.
        |
        */

        if (
            is_numeric(
                $value
            )
        ) {
            $rate =
                (float)
                $value;


            if (
                $rate > 0
                &&
                $rate <= 1
            ) {
                return round(
                    $rate
                    *
                    100,
                    6
                );
            }


            return $rate;
        }


        $clean =
            trim(
                (string)
                $value
            );


        if (
            $clean === ''
        ) {
            return 0.0;
        }


        /*
        |--------------------------------------------------------------------------
        | Explicit Percentage Text
        |--------------------------------------------------------------------------
        |
        | "6%" => 6
        | "17.3%" => 17.3
        |
        */

        $containsPercent =
            str_contains(
                $clean,
                '%'
            );


        $clean =
            str_ireplace(
                [
                    '%',
                    ',',
                    ' ',
                ],
                '',
                $clean
            );


        if (
            !is_numeric(
                $clean
            )
        ) {
            return 0.0;
        }


        $rate =
            (float)
            $clean;


        if ($containsPercent) {
            return $rate;
        }


        if (
            $rate > 0
            &&
            $rate <= 1
        ) {
            return round(
                $rate
                *
                100,
                6
            );
        }


        return $rate;
    }


    /*
    |--------------------------------------------------------------------------
    | Date Value
    |--------------------------------------------------------------------------
    */

    private function dateValue(
        mixed $value
    ): ?string {
        if (
            $value === null
        ) {
            return null;
        }


        if (
            trim(
                (string)
                $value
            )
            === ''
        ) {
            return null;
        }


        try {

            /*
            |--------------------------------------------------------------------------
            | Excel Serial Date
            |--------------------------------------------------------------------------
            */

            if (
                is_numeric(
                    $value
                )
            ) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )
                    ->toDateString();
            }


            /*
            |--------------------------------------------------------------------------
            | Text Date
            |--------------------------------------------------------------------------
            */

            return Carbon::parse(
                (string)
                $value
            )
                ->toDateString();

        } catch (Throwable) {

            /*
            |--------------------------------------------------------------------------
            | Invalid Date
            |--------------------------------------------------------------------------
            |
            | Return null.
            |
            | ContributionImportValidator will determine whether this becomes
            | a warning or an error depending on whether the member is existing
            | or new.
            |
            */

            return null;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Empty Row
    |--------------------------------------------------------------------------
    */

    private function isEmptyRow(
        array $values
    ): bool {
        foreach (
            $values
            as $value
        ) {
            if (
                $value !== null
                &&
                trim(
                    (string)
                    $value
                )
                !== ''
            ) {
                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | Total Row
    |--------------------------------------------------------------------------
    */

    private function looksLikeTotalRow(
        array $values
    ): bool {
        foreach (
            array_slice(
                $values,
                0,
                10
            )
            as $value
        ) {
            $text =
                strtoupper(
                    trim(
                        (string)
                        $value
                    )
                );


            if (
                in_array(
                    $text,
                    [
                        'TOTAL',
                        'TOTALS',
                        'GRAND TOTAL',
                        'GRAND TOTALS',
                    ],
                    true
                )
            ) {
                return true;
            }
        }


        return false;
    }
}