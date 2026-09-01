<?php

namespace App\Services\PensionsAdministration\HistoricalContributions;

use Carbon\Carbon;
use Generator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

class HistoricalContributionExcelReader
{
    /*
    |--------------------------------------------------------------------------
    | NORMAL HISTORICAL PERIOD
    |--------------------------------------------------------------------------
    |
    | Permanent historical migration range:
    |
    | January 2009 -> October 2025
    |
    */

    private const START_YEAR = 2009;

    private const START_MONTH = 1;

    private const END_YEAR = 2025;

    private const END_MONTH = 10;

    private const HEADER_ROW = 1;

    /*
    |--------------------------------------------------------------------------
    | Optimised Chunk Size
    |--------------------------------------------------------------------------
    */

    private const READ_CHUNK_SIZE = 250;

    /*
    |--------------------------------------------------------------------------
    | Inspect Workbook
    |--------------------------------------------------------------------------
    */

    public function inspect(
        string $path
    ): array {
        if (!is_file($path)) {
            throw new RuntimeException(
                'Historical contribution workbook could not be found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Header Reader
        |--------------------------------------------------------------------------
        */

        $reader =
            IOFactory::createReaderForFile(
                $path
            );

        $reader->setReadDataOnly(
            true
        );

        $reader->setReadFilter(
            new HistoricalContributionHeaderReadFilter()
        );

        $spreadsheet =
            $reader->load(
                $path
            );

        $sheet =
            $spreadsheet->getSheet(0);

        $highestColumn =
            $sheet->getHighestColumn();

        $highestColumnIndex =
            Coordinate::columnIndexFromString(
                $highestColumn
            );

        /*
        |--------------------------------------------------------------------------
        | Header Map
        |--------------------------------------------------------------------------
        */

        $headers =
            [];

        for (
            $column = 1;
            $column <= $highestColumnIndex;
            $column++
        ) {
            $value =
                $sheet
                    ->getCell([
                        $column,
                        self::HEADER_ROW,
                    ])
                    ->getValue();

            $normalized =
                $this->normalizeHeader(
                    $value
                );

            if ($normalized === '') {
                continue;
            }

            $headers[
                $normalized
            ] =
                $column;
        }

        /*
        |--------------------------------------------------------------------------
        | Required Static Columns
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'penad_employer_number',
                'surname',
                'first_names',
            ]
            as $required
        ) {
            if (
                !isset(
                    $headers[
                        $required
                    ]
                )
            ) {
                throw new RuntimeException(
                    'Historical contribution workbook is missing required column: '
                    . $required
                    . '.'
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Discover Historical Financial Columns
        |--------------------------------------------------------------------------
        */

        $financialColumns =
            $this->discoverFinancialColumns(
                $headers
            );

        /*
        |--------------------------------------------------------------------------
        | Validate January 2009
        |--------------------------------------------------------------------------
        */

        $firstConfiguredPeriod =
            $financialColumns[
                'periods'
            ][0]
            ??
            null;

        if (!$firstConfiguredPeriod) {
            throw new RuntimeException(
                'No historical monthly contribution periods were detected.'
            );
        }

        if (
            (int) $firstConfiguredPeriod[
                'period_year'
            ]
            !==
            2009
            ||
            (int) $firstConfiguredPeriod[
                'period_month'
            ]
            !==
            1
        ) {
            throw new RuntimeException(
                'Historical contribution reader is not configured to start at January 2009.'
            );
        }

        if (
            !$firstConfiguredPeriod[
                'basic_pay_column'
            ]
            &&
            !$firstConfiguredPeriod[
                'employee_contribution_column'
            ]
            &&
            !$firstConfiguredPeriod[
                'employer_contribution_column'
            ]
            &&
            !$firstConfiguredPeriod[
                'employee_avc_column'
            ]
            &&
            !$firstConfiguredPeriod[
                'employer_avc_column'
            ]
        ) {
            throw new RuntimeException(
                'January 2009 salary/contribution columns were not detected. Make sure you are using the January 2009 to October 2025 Salary + AVC template.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validate October 2025
        |--------------------------------------------------------------------------
        */

        $lastConfiguredPeriod =
            end(
                $financialColumns[
                    'periods'
                ]
            );

        if (
            !$lastConfiguredPeriod
            ||
            (int) $lastConfiguredPeriod[
                'period_year'
            ]
            !==
            2025
            ||
            (int) $lastConfiguredPeriod[
                'period_month'
            ]
            !==
            10
        ) {
            throw new RuntimeException(
                'Historical contribution reader is not configured to end at October 2025.'
            );
        }

        if (
            !$lastConfiguredPeriod[
                'basic_pay_column'
            ]
            &&
            !$lastConfiguredPeriod[
                'employee_contribution_column'
            ]
            &&
            !$lastConfiguredPeriod[
                'employer_contribution_column'
            ]
            &&
            !$lastConfiguredPeriod[
                'employee_avc_column'
            ]
            &&
            !$lastConfiguredPeriod[
                'employer_avc_column'
            ]
        ) {
            throw new RuntimeException(
                'October 2025 salary/contribution columns were not detected in the workbook.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Required Columns
        |--------------------------------------------------------------------------
        */

        $requiredColumnIndexes =
            $this->buildRequiredColumnIndexes(
                headers:
                    $headers,

                financialColumns:
                    $financialColumns
            );

        /*
        |--------------------------------------------------------------------------
        | Release Header Workbook
        |--------------------------------------------------------------------------
        */

        $spreadsheet
            ->disconnectWorksheets();

        unset(
            $spreadsheet,
            $sheet,
            $reader
        );

        /*
        |--------------------------------------------------------------------------
        | Workbook Metadata
        |--------------------------------------------------------------------------
        */

        $infoReader =
            IOFactory::createReaderForFile(
                $path
            );

        $worksheetInfo =
            $infoReader
                ->listWorksheetInfo(
                    $path
                );

        $highestRow =
            (int) (
                $worksheetInfo[0][
                    'totalRows'
                ]
                ??
                0
            );

        unset(
            $infoReader
        );

        gc_collect_cycles();

        return [
            'sheet_index' =>
                0,

            'header_row' =>
                self::HEADER_ROW,

            'highest_row' =>
                $highestRow,

            'highest_column' =>
                $highestColumn,

            'highest_column_index' =>
                $highestColumnIndex,

            'estimated_source_rows' =>
                max(
                    0,
                    $highestRow
                    -
                    self::HEADER_ROW
                ),

            'headers' =>
                $headers,

            'financial_columns' =>
                $financialColumns,

            'required_column_indexes' =>
                $requiredColumnIndexes,

            'configured_start_year' =>
                self::START_YEAR,

            'configured_start_month' =>
                self::START_MONTH,

            'configured_end_year' =>
                self::END_YEAR,

            'configured_end_month' =>
                self::END_MONTH,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Stream Workbook Rows
    |--------------------------------------------------------------------------
    */

    public function rows(
        string $path,
        array $inspection
    ): Generator {
        $highestRow =
            (int) (
                $inspection[
                    'highest_row'
                ]
                ??
                0
            );

        $headers =
            $inspection[
                'headers'
            ]
            ??
            [];

        $financialColumns =
            $inspection[
                'financial_columns'
            ]
            ??
            [];

        $requiredColumnIndexes =
            $inspection[
                'required_column_indexes'
            ]
            ??
            [];

        if (
            $highestRow
            <=
            self::HEADER_ROW
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Chunk Through Members
        |--------------------------------------------------------------------------
        */

        for (
            $startRow =
                self::HEADER_ROW
                +
                1;

            $startRow
            <=
            $highestRow;

            $startRow +=
                self::READ_CHUNK_SIZE
        ) {
            $endRow =
                min(
                    $highestRow,
                    $startRow
                    +
                    self::READ_CHUNK_SIZE
                    -
                    1
                );

            $reader =
                IOFactory::createReaderForFile(
                    $path
                );

            $reader->setReadDataOnly(
                true
            );

            $reader->setReadFilter(
                new HistoricalContributionChunkReadFilter(
                    startRow:
                        $startRow,

                    endRow:
                        $endRow,

                    allowedColumnIndexes:
                        $requiredColumnIndexes
                )
            );

            $spreadsheet =
                $reader->load(
                    $path
                );

            $sheet =
                $spreadsheet->getSheet(0);

            /*
            |--------------------------------------------------------------------------
            | Read Members
            |--------------------------------------------------------------------------
            */

            for (
                $rowNumber =
                    $startRow;

                $rowNumber
                <=
                $endRow;

                $rowNumber++
            ) {
                /*
                |--------------------------------------------------------------------------
                | Skip Empty Source Rows
                |--------------------------------------------------------------------------
                */

                if (
                    $this->isEmptySourceRow(
                        sheet:
                            $sheet,

                        rowNumber:
                            $rowNumber,

                        headers:
                            $headers
                    )
                ) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Member Data
                |--------------------------------------------------------------------------
                */

                $memberData =
                    $this->readMemberData(
                        sheet:
                            $sheet,

                        rowNumber:
                            $rowNumber,

                        headers:
                            $headers
                    );

                /*
                |--------------------------------------------------------------------------
                | January 2009 Take-On
                |--------------------------------------------------------------------------
                */

                $takeOn =
                    $this->readTakeOn(
                        sheet:
                            $sheet,

                        rowNumber:
                            $rowNumber,

                        columns:
                            $financialColumns[
                                'take_on'
                            ]
                            ??
                            []
                    );

                /*
                |--------------------------------------------------------------------------
                | January 2009 -> October 2025
                |--------------------------------------------------------------------------
                */

                $periods =
                    [];

                foreach (
                    $financialColumns[
                        'periods'
                    ]
                    ??
                    []
                    as $definition
                ) {
                    $periods[] =
                        $this->readPeriod(
                            sheet:
                                $sheet,

                            rowNumber:
                                $rowNumber,

                            definition:
                                $definition
                        );
                }

                yield [
                    'source_row_number' =>
                        $rowNumber,

                    'member_data' =>
                        $memberData,

                    'take_on' =>
                        $takeOn,

                    'periods' =>
                        $periods,
                ];
            }

            /*
            |--------------------------------------------------------------------------
            | Release Memory
            |--------------------------------------------------------------------------
            */

            $spreadsheet
                ->disconnectWorksheets();

            unset(
                $spreadsheet,
                $sheet,
                $reader
            );

            gc_collect_cycles();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Discover Financial Columns
    |--------------------------------------------------------------------------
    */

    private function discoverFinancialColumns(
        array $headers
    ): array {
        /*
        |--------------------------------------------------------------------------
        | January 2009 Take-On
        |--------------------------------------------------------------------------
        */

        $takeOn = [
            'employee_contribution' =>
                $this->firstExistingHeader(
                    $headers,
                    [
                        'jan_2009_take_on_employee_contribution',
                        'jan_2009_takeon_employee_contribution',
                        '2008_employee_contribution',
                        '2008_employee_contributions',
                    ]
                ),

            'employer_contribution' =>
                $this->firstExistingHeader(
                    $headers,
                    [
                        'jan_2009_take_on_employer_contribution',
                        'jan_2009_takeon_employer_contribution',
                        '2008_employer_contribution',
                        '2008_employer_contributions',
                    ]
                ),

            'employee_avc' =>
                $this->firstExistingHeader(
                    $headers,
                    [
                        'jan_2009_take_on_employee_avc',
                        'jan_2009_takeon_employee_avc',
                    ]
                ),

            'employer_avc' =>
                $this->firstExistingHeader(
                    $headers,
                    [
                        'jan_2009_take_on_employer_avc',
                        'jan_2009_takeon_employer_avc',
                    ]
                ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Monthly Financial Columns
        |--------------------------------------------------------------------------
        */

        $periods =
            [];

        for (
            $year =
                self::START_YEAR;

            $year
            <=
            self::END_YEAR;

            $year++
        ) {
            /*
            |--------------------------------------------------------------------------
            | First Month
            |--------------------------------------------------------------------------
            */

            $firstMonth =
                $year
                ===
                self::START_YEAR
                    ? self::START_MONTH
                    : 1;

            /*
            |--------------------------------------------------------------------------
            | Last Month
            |--------------------------------------------------------------------------
            */

            $lastMonth =
                $year
                ===
                self::END_YEAR
                    ? self::END_MONTH
                    : 12;

            for (
                $month =
                    $firstMonth;

                $month
                <=
                $lastMonth;

                $month++
            ) {
                $periodDate =
                    Carbon::create(
                        $year,
                        $month,
                        1
                    );

                $monthShort =
                    strtolower(
                        $periodDate->format(
                            'M'
                        )
                    );

                $monthLong =
                    strtolower(
                        $periodDate->format(
                            'F'
                        )
                    );

                $shortPrefix =
                    $monthShort
                    .
                    '_'
                    .
                    $year;

                $longPrefix =
                    $monthLong
                    .
                    '_'
                    .
                    $year;

                $periods[] = [
                    'period_year' =>
                        $year,

                    'period_month' =>
                        $month,

                    'period_date' =>
                        $periodDate
                            ->copy()
                            ->endOfMonth()
                            ->toDateString(),

                    /*
                    |--------------------------------------------------------------------------
                    | Basic Salary
                    |--------------------------------------------------------------------------
                    */

                    'basic_pay_column' =>
                        $this->firstExistingHeader(
                            $headers,
                            [
                                $shortPrefix . '_basic_salary',
                                $shortPrefix . '_basic_pay',
                                $shortPrefix . '_salary',

                                $longPrefix . '_basic_salary',
                                $longPrefix . '_basic_pay',
                                $longPrefix . '_salary',
                            ]
                        ),

                    /*
                    |--------------------------------------------------------------------------
                    | Employee Contribution
                    |--------------------------------------------------------------------------
                    */

                    'employee_contribution_column' =>
                        $this->firstExistingHeader(
                            $headers,
                            [
                                $shortPrefix . '_employee_contribution',
                                $shortPrefix . '_employee_contributions',

                                $longPrefix . '_employee_contribution',
                                $longPrefix . '_employee_contributions',
                            ]
                        ),

                    /*
                    |--------------------------------------------------------------------------
                    | Employer Contribution
                    |--------------------------------------------------------------------------
                    */

                    'employer_contribution_column' =>
                        $this->firstExistingHeader(
                            $headers,
                            [
                                $shortPrefix . '_employer_contribution',
                                $shortPrefix . '_employer_contributions',

                                $longPrefix . '_employer_contribution',
                                $longPrefix . '_employer_contributions',
                            ]
                        ),

                    /*
                    |--------------------------------------------------------------------------
                    | Employee AVC
                    |--------------------------------------------------------------------------
                    */

                    'employee_avc_column' =>
                        $this->firstExistingHeader(
                            $headers,
                            [
                                $shortPrefix . '_employee_avc',
                                $longPrefix . '_employee_avc',
                            ]
                        ),

                    /*
                    |--------------------------------------------------------------------------
                    | Employer AVC
                    |--------------------------------------------------------------------------
                    */

                    'employer_avc_column' =>
                        $this->firstExistingHeader(
                            $headers,
                            [
                                $shortPrefix . '_employer_avc',
                                $longPrefix . '_employer_avc',
                            ]
                        ),

                    'source_reference' =>
                        $periodDate
                            ->format(
                                'F Y'
                            ),
                ];
            }
        }

        return [
            'take_on' =>
                $takeOn,

            'periods' =>
                $periods,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Required Column Indexes
    |--------------------------------------------------------------------------
    */

    private function buildRequiredColumnIndexes(
        array $headers,
        array $financialColumns
    ): array {
        $columnIndexes =
            [];

        /*
        |--------------------------------------------------------------------------
        | Static Member Columns
        |--------------------------------------------------------------------------
        */

        $staticHeaders = [
            'penad_employer_number',
            'employer_name',

            'staff_number',
            'vote_number',

            'penerp_member_number',
            'penad_member_number',
            'fundworx_member_number',

            'title',
            'surname',
            'first_names',
            'other_names',
            'maiden_name',

            'national_id',
            'date_of_birth',
            'gender',
            'marital_status',

            'date_joined_fund',
            'date_joined_employer',
            'membership_status',
            'exit_date',
            'exit_reason',

            'occupation',

            'email',
            'cell_number',

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

        foreach (
            $staticHeaders
            as $header
        ) {
            if (
                isset(
                    $headers[
                        $header
                    ]
                )
            ) {
                $columnIndexes[] =
                    (int) $headers[
                        $header
                    ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | January 2009 Take-On Columns
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'employee_contribution',
                'employer_contribution',
                'employee_avc',
                'employer_avc',
            ]
            as $columnKey
        ) {
            $columnIndex =
                $financialColumns[
                    'take_on'
                ][
                    $columnKey
                ]
                ??
                null;

            if ($columnIndex) {
                $columnIndexes[] =
                    (int) $columnIndex;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Jan 2009 -> Oct 2025 Monthly Columns
        |--------------------------------------------------------------------------
        */

        foreach (
            $financialColumns[
                'periods'
            ]
            ??
            []
            as $period
        ) {
            foreach (
                [
                    'basic_pay_column',
                    'employee_contribution_column',
                    'employer_contribution_column',
                    'employee_avc_column',
                    'employer_avc_column',
                ]
                as $columnKey
            ) {
                $columnIndex =
                    $period[
                        $columnKey
                    ]
                    ??
                    null;

                if ($columnIndex) {
                    $columnIndexes[] =
                        (int) $columnIndex;
                }
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Unique / Sorted
        |--------------------------------------------------------------------------
        */

        $columnIndexes =
            array_values(
                array_unique(
                    $columnIndexes
                )
            );

        sort(
            $columnIndexes
        );

        return $columnIndexes;
    }

    /*
    |--------------------------------------------------------------------------
    | Find Header
    |--------------------------------------------------------------------------
    */

    private function firstExistingHeader(
        array $headers,
        array $candidates
    ): ?int {
        foreach (
            $candidates
            as $candidate
        ) {
            if (
                isset(
                    $headers[
                        $candidate
                    ]
                )
            ) {
                return (int)
                    $headers[
                        $candidate
                    ];
            }
        }

        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | Read January 2009 Take-On
    |--------------------------------------------------------------------------
    */

    private function readTakeOn(
        mixed $sheet,
        int $rowNumber,
        array $columns
    ): ?array {
        $employeeContributionRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columns[
                    'employee_contribution'
                ]
                ??
                null
            );

        $employerContributionRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columns[
                    'employer_contribution'
                ]
                ??
                null
            );

        $employeeAvcRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columns[
                    'employee_avc'
                ]
                ??
                null
            );

        $employerAvcRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columns[
                    'employer_avc'
                ]
                ??
                null
            );

        /*
        |--------------------------------------------------------------------------
        | No Take-On
        |--------------------------------------------------------------------------
        */

        if (
            $this->isBlank(
                $employeeContributionRaw
            )
            &&
            $this->isBlank(
                $employerContributionRaw
            )
            &&
            $this->isBlank(
                $employeeAvcRaw
            )
            &&
            $this->isBlank(
                $employerAvcRaw
            )
        ) {
            return null;
        }

        return [
            'period_year' =>
                2009,

            'period_month' =>
                1,

            'period_date' =>
                '2009-01-31',

            'transaction_type' =>
                'take_on',

            'service_status' =>
                $this->determineFinancialStatus(
                    basicPayRaw:
                        null,

                    employeeContributionRaw:
                        $employeeContributionRaw,

                    employerContributionRaw:
                        $employerContributionRaw,

                    employeeAvcRaw:
                        $employeeAvcRaw,

                    employerAvcRaw:
                        $employerAvcRaw
                ),

            'basic_pay' =>
                null,

            'employee_rate' =>
                null,

            'employer_rate' =>
                null,

            'employee_contribution' =>
                $this->decimalOrNull(
                    $employeeContributionRaw
                ),

            'employer_contribution' =>
                $this->decimalOrNull(
                    $employerContributionRaw
                ),

            'employee_avc' =>
                $this->decimalOrNull(
                    $employeeAvcRaw
                ),

            'employer_avc' =>
                $this->decimalOrNull(
                    $employerAvcRaw
                ),

            'employee_contribution_was_blank' =>
                $this->isBlank(
                    $employeeContributionRaw
                ),

            'employer_contribution_was_blank' =>
                $this->isBlank(
                    $employerContributionRaw
                ),

            'source_reference' =>
                'January 2009 Take-On',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Read Monthly Period
    |--------------------------------------------------------------------------
    */

    private function readPeriod(
        mixed $sheet,
        int $rowNumber,
        array $definition
    ): array {
        $basicPayRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $definition[
                    'basic_pay_column'
                ]
                ??
                null
            );

        $employeeContributionRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $definition[
                    'employee_contribution_column'
                ]
                ??
                null
            );

        $employerContributionRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $definition[
                    'employer_contribution_column'
                ]
                ??
                null
            );

        $employeeAvcRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $definition[
                    'employee_avc_column'
                ]
                ??
                null
            );

        $employerAvcRaw =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $definition[
                    'employer_avc_column'
                ]
                ??
                null
            );

        return [
            'period_year' =>
                (int) $definition[
                    'period_year'
                ],

            'period_month' =>
                (int) $definition[
                    'period_month'
                ],

            'period_date' =>
                $definition[
                    'period_date'
                ],

            'transaction_type' =>
                'expected',

            'service_status' =>
                $this->determineFinancialStatus(
                    basicPayRaw:
                        $basicPayRaw,

                    employeeContributionRaw:
                        $employeeContributionRaw,

                    employerContributionRaw:
                        $employerContributionRaw,

                    employeeAvcRaw:
                        $employeeAvcRaw,

                    employerAvcRaw:
                        $employerAvcRaw
                ),

            'basic_pay' =>
                $this->decimalOrNull(
                    $basicPayRaw
                ),

            'employee_rate' =>
                null,

            'employer_rate' =>
                null,

            'employee_contribution' =>
                $this->decimalOrNull(
                    $employeeContributionRaw
                ),

            'employer_contribution' =>
                $this->decimalOrNull(
                    $employerContributionRaw
                ),

            'employee_avc' =>
                $this->decimalOrNull(
                    $employeeAvcRaw
                ),

            'employer_avc' =>
                $this->decimalOrNull(
                    $employerAvcRaw
                ),

            'employee_contribution_was_blank' =>
                $this->isBlank(
                    $employeeContributionRaw
                ),

            'employer_contribution_was_blank' =>
                $this->isBlank(
                    $employerContributionRaw
                ),

            'source_reference' =>
                $definition[
                    'source_reference'
                ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Status
    |--------------------------------------------------------------------------
    */

    private function determineFinancialStatus(
        mixed $basicPayRaw,
        mixed $employeeContributionRaw,
        mixed $employerContributionRaw,
        mixed $employeeAvcRaw,
        mixed $employerAvcRaw
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Completely Blank
        |--------------------------------------------------------------------------
        */

        $allBlank =
            $this->isBlank(
                $basicPayRaw
            )
            &&
            $this->isBlank(
                $employeeContributionRaw
            )
            &&
            $this->isBlank(
                $employerContributionRaw
            )
            &&
            $this->isBlank(
                $employeeAvcRaw
            )
            &&
            $this->isBlank(
                $employerAvcRaw
            );

        if ($allBlank) {
            return 'blank';
        }

        /*
        |--------------------------------------------------------------------------
        | Numeric Values
        |--------------------------------------------------------------------------
        */

        $employeeContribution =
            $this->numericValue(
                $employeeContributionRaw
            );

        $employerContribution =
            $this->numericValue(
                $employerContributionRaw
            );

        $employeeAvc =
            $this->numericValue(
                $employeeAvcRaw
            );

        $employerAvc =
            $this->numericValue(
                $employerAvcRaw
            );

        /*
        |--------------------------------------------------------------------------
        | Any Contribution / AVC
        |--------------------------------------------------------------------------
        */

        if (
            abs($employeeContribution) > 0.0000001
            ||
            abs($employerContribution) > 0.0000001
            ||
            abs($employeeAvc) > 0.0000001
            ||
            abs($employerAvc) > 0.0000001
        ) {
            return 'contributed';
        }

        /*
        |--------------------------------------------------------------------------
        | Explicit Zero
        |--------------------------------------------------------------------------
        */

        return 'zero_contribution';
    }

    /*
    |--------------------------------------------------------------------------
    | Read Member Data
    |--------------------------------------------------------------------------
    */

    private function readMemberData(
        mixed $sheet,
        int $rowNumber,
        array $headers
    ): array {
        return [
            'penad_employer_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'penad_employer_number'
                    ]
                    ??
                    null
                ),

            'employer_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'penad_employer_number'
                    ]
                    ??
                    null
                ),

            'penerp_employer_number' =>
                null,

            'fundworx_employer_number' =>
                null,

            'employer_name' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'employer_name'
                    ]
                    ??
                    null
                ),

            'penerp_member_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'penerp_member_number'
                    ]
                    ??
                    null
                ),

            'penad_member_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'penad_member_number'
                    ]
                    ??
                    null
                ),

            'legacy_member_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'penad_member_number'
                    ]
                    ??
                    null
                ),

            'fundworx_member_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'fundworx_member_number'
                    ]
                    ??
                    null
                ),

            'staff_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'staff_number'
                    ]
                    ??
                    null
                ),

            'vote_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'vote_number'
                    ]
                    ??
                    null
                ),

            'title' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'title'
                    ]
                    ??
                    null
                ),

            'surname' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'surname'
                    ]
                    ??
                    null
                ),

            'first_names' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'first_names'
                    ]
                    ??
                    null
                ),

            'other_names' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'other_names'
                    ]
                    ??
                    null
                ),

            'maiden_name' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'maiden_name'
                    ]
                    ??
                    null
                ),

            'national_id' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'national_id'
                    ]
                    ??
                    null
                ),

            'date_of_birth' =>
                $this->dateCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'date_of_birth'
                    ]
                    ??
                    null
                ),

            'gender' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'gender'
                    ]
                    ??
                    null
                ),

            'marital_status' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'marital_status'
                    ]
                    ??
                    null
                ),

            'date_joined_fund' =>
                $this->dateCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'date_joined_fund'
                    ]
                    ??
                    null
                ),

            'date_joined_employer' =>
                $this->dateCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'date_joined_employer'
                    ]
                    ??
                    null
                ),

            'membership_status_raw' =>
    $this->textCell(
        $sheet,
        $rowNumber,
        $headers['membership_status'] ?? null
    ),

'membership_status' =>
    HistoricalMembershipStatus::normalize(
        $this->textCell(
            $sheet,
            $rowNumber,
            $headers['membership_status'] ?? null
        )
    ),

            'exit_date' =>
            $this->dateCell(
                $sheet,
                $rowNumber,
                $headers[
                    'exit_date'
                ]
                ??
                null
            ),

        'exit_reason' =>
            $this->textCell(
                $sheet,
                $rowNumber,
                $headers[
                    'exit_reason'
                ]
                ??
                null
            ),
            'occupation' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'occupation'
                    ]
                    ??
                    null
                ),

            'email' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'email'
                    ]
                    ??
                    null
                ),

            'secondary_email' =>
                null,

            'cell_number' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'cell_number'
                    ]
                    ??
                    null
                ),

            'secondary_cell_number' =>
                null,

            'physical_address_1' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_address_1'
                    ]
                    ??
                    null
                ),

            'physical_address_2' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_address_2'
                    ]
                    ??
                    null
                ),

            'physical_address_3' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_address_3'
                    ]
                    ??
                    null
                ),

            'physical_suburb' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_suburb'
                    ]
                    ??
                    null
                ),

            'physical_city' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_city'
                    ]
                    ??
                    null
                ),

            'physical_country' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'physical_country'
                    ]
                    ??
                    null
                ),

            'postal_address_1' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'postal_address_1'
                    ]
                    ??
                    null
                ),

            'postal_address_2' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'postal_address_2'
                    ]
                    ??
                    null
                ),

            'postal_address_3' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'postal_address_3'
                    ]
                    ??
                    null
                ),

            'postal_city' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'postal_city'
                    ]
                    ??
                    null
                ),

            'postal_country' =>
                $this->textCell(
                    $sheet,
                    $rowNumber,
                    $headers[
                        'postal_country'
                    ]
                    ??
                    null
                ),

            'currency_code' =>
                null,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Empty Source Row
    |--------------------------------------------------------------------------
    */

    private function isEmptySourceRow(
        mixed $sheet,
        int $rowNumber,
        array $headers
    ): bool {
        foreach (
            [
                'penad_employer_number',
                'staff_number',
                'penerp_member_number',
                'penad_member_number',
                'fundworx_member_number',
                'national_id',
                'surname',
                'first_names',
            ]
            as $header
        ) {
            if (
                !isset(
                    $headers[
                        $header
                    ]
                )
            ) {
                continue;
            }

            $value =
                $this->cellValue(
                    $sheet,
                    $rowNumber,
                    $headers[
                        $header
                    ]
                );

            if (
                !$this->isBlank(
                    $value
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | Cell Value
    |--------------------------------------------------------------------------
    */

    private function cellValue(
        mixed $sheet,
        int $rowNumber,
        ?int $columnNumber
    ): mixed {
        if (!$columnNumber) {
            return null;
        }

        $cell =
            $sheet
                ->getCell([
                    $columnNumber,
                    $rowNumber,
                ]);

        if (
            $cell->isFormula()
        ) {
            try {
                return $cell
                    ->getCalculatedValue();
            } catch (Throwable) {
                return $cell
                    ->getOldCalculatedValue();
            }
        }

        return $cell->getValue();
    }

    /*
    |--------------------------------------------------------------------------
    | Text
    |--------------------------------------------------------------------------
    */

    private function textCell(
        mixed $sheet,
        int $rowNumber,
        ?int $columnNumber
    ): ?string {
        $value =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columnNumber
            );

        if (
            $this->isBlank(
                $value
            )
        ) {
            return null;
        }

        return trim(
            (string) $value
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Date
    |--------------------------------------------------------------------------
    */

    private function dateCell(
        mixed $sheet,
        int $rowNumber,
        ?int $columnNumber
    ): ?string {
        $value =
            $this->cellValue(
                $sheet,
                $rowNumber,
                $columnNumber
            );

        if (
            $this->isBlank(
                $value
            )
        ) {
            return null;
        }

        if (
            is_numeric(
                $value
            )
        ) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->toDateString();
            } catch (Throwable) {
                return null;
            }
        }

        try {
            return Carbon::parse(
                trim(
                    (string) $value
                )
            )->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Decimal
    |--------------------------------------------------------------------------
    */

    private function decimalOrNull(
        mixed $value
    ): ?string {
        if (
            $this->isBlank(
                $value
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
                trim(
                    $value
                );

            $value =
                str_replace(
                    [
                        ',',
                        ' ',
                        '$',
                    ],
                    '',
                    $value
                );

            if (
                str_starts_with(
                    $value,
                    '('
                )
                &&
                str_ends_with(
                    $value,
                    ')'
                )
            ) {
                $value =
                    '-'
                    .
                    trim(
                        $value,
                        '()'
                    );
            }
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
    | Numeric
    |--------------------------------------------------------------------------
    */

    private function numericValue(
        mixed $value
    ): float {
        $value =
            $this->decimalOrNull(
                $value
            );

        return
            $value === null
                ? 0.0
                : (float) $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Blank
    |--------------------------------------------------------------------------
    |
    | 0.0000 is NOT blank.
    |
    */

    private function isBlank(
        mixed $value
    ): bool {
        if (
            $value === null
        ) {
            return true;
        }

        if (
            is_string(
                $value
            )
        ) {
            return trim(
                $value
            )
            ===
            '';
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Normalize Header
    |--------------------------------------------------------------------------
    */

    private function normalizeHeader(
        mixed $header
    ): string {
        if (
            $header === null
        ) {
            return '';
        }

        $header =
            strtolower(
                trim(
                    (string) $header
                )
            );

        $header =
            preg_replace(
                '/\s+/',
                ' ',
                $header
            );

        $header =
            preg_replace(
                '/[^a-z0-9]+/',
                '_',
                $header
            );

        return trim(
            (string) $header,
            '_'
        );
    }
}

/*
|--------------------------------------------------------------------------
| Header Filter
|--------------------------------------------------------------------------
*/

class HistoricalContributionHeaderReadFilter implements IReadFilter
{
    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        return
            $row === 1;
    }
}

/*
|--------------------------------------------------------------------------
| Optimised Chunk Filter
|--------------------------------------------------------------------------
*/

class HistoricalContributionChunkReadFilter implements IReadFilter
{
    private array $allowedColumnIndexes =
        [];

    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
        array $allowedColumnIndexes = []
    ) {
        foreach (
            $allowedColumnIndexes
            as $columnIndex
        ) {
            $this->allowedColumnIndexes[
                (int) $columnIndex
            ] =
                true;
        }
    }

    public function readCell(
        string $columnAddress,
        int $row,
        string $worksheetName = ''
    ): bool {
        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        if (
            $row === 1
        ) {
            return true;
        }

        /*
        |--------------------------------------------------------------------------
        | Outside Current Source Row Chunk
        |--------------------------------------------------------------------------
        */

        if (
            $row < $this->startRow
            ||
            $row > $this->endRow
        ) {
            return false;
        }

        /*
        |--------------------------------------------------------------------------
        | Required Columns Only
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $this->allowedColumnIndexes
            )
        ) {
            return true;
        }

        $columnIndex =
            Coordinate::columnIndexFromString(
                $columnAddress
            );

        return isset(
            $this->allowedColumnIndexes[
                $columnIndex
            ]
        );
    }
}