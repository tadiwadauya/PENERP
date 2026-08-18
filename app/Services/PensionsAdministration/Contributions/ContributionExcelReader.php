<?php

namespace App\Services\PensionsAdministration\Contributions;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
    | The first value in each list is our preferred PENERP template heading.
    |
    | We also support older employer schedules containing spaces,
    | alternative descriptions and legacy USD / ZWG-specific headings.
    |
    */

    private array $aliases = [

        /*
        |--------------------------------------------------------------------------
        | Employer / Scheme
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
        | Member Details
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

        'national_id' => [
            'national_id',
            'national id',
            'national id no',
            'national id number',
            'national registration number',
            'id number',
            'nationalidno',
        ],

        'date_joined_fund' => [
            'date_joined_fund',
            'date joined fund',
            'fund join date',
            'date joined scheme',
        ],

        'date_joined_employer' => [
            'date_joined_employer',
            'date joined employer',
            'employment date',
            'date employed',
        ],

        'staff_number' => [
            'staff_number',
            'staff number',
            'staff no',
            'staff no.',
            'employee number',
            'employee code',
            'employee code or works number',
            'works number',
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
            'pension reference number',
            'pension reference',
        ],

        'pension_reference_number' => [
            'pension_reference_number',
            'pension reference number',
            'pension reference',
            'penad member number',
            'penad number',
            'member number',
            'member no',
            'member no.',
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
        | These are the preferred columns in the new PENERP template.
        |
        | The contribution upload screen determines whether these values are
        | mapped to ZWG or USD.
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
        | Legacy USD Columns
        |--------------------------------------------------------------------------
        |
        | These are retained for compatibility with older schedules.
        |
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
        | Legacy ZWG Columns
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
    | Required Template Columns
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | We no longer require:
    |
    | usd_employee_contribution
    | usd_employer_contribution
    |
    | because the upload currency is selected separately.
    |
    | The generic contribution fields work for either ZWG or USD.
    |
    */

    private array $requiredFields = [
        'surname',
        'first_names',
        'staff_number',
        'employee_contribution',
        'employer_contribution',
    ];


    /*
    |--------------------------------------------------------------------------
    | Read Excel File
    |--------------------------------------------------------------------------
    */

    public function read(
        string $path
    ): array {
        if (
            !file_exists(
                $path
            )
        ) {
            throw new RuntimeException(
                'The contribution Excel file could not be found.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Load Spreadsheet
        |--------------------------------------------------------------------------
        */

        $spreadsheet =
            IOFactory::load(
                $path
            );


        $sheet =
            $spreadsheet
                ->getActiveSheet();


        /*
        |--------------------------------------------------------------------------
        | Spreadsheet Dimensions
        |--------------------------------------------------------------------------
        */

        $highestRow =
            $sheet
                ->getHighestDataRow();


        $highestColumn =
            $sheet
                ->getHighestDataColumn();


        if (
            $highestRow < 2
        ) {
            throw new RuntimeException(
                'The contribution Excel file does not contain any contribution rows.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Header Row
        |--------------------------------------------------------------------------
        */

        $headerRow =
            $sheet->rangeToArray(
                'A1:'
                . $highestColumn
                . '1',
                null,
                true,
                false
            )[0];


        /*
        |--------------------------------------------------------------------------
        | Build Column Map
        |--------------------------------------------------------------------------
        */

        $columnMap =
            $this->buildColumnMap(
                $headerRow
            );


        /*
        |--------------------------------------------------------------------------
        | Validate Template
        |--------------------------------------------------------------------------
        */

        $this->validateRequiredColumns(
            $columnMap
        );


        /*
        |--------------------------------------------------------------------------
        | Read Contribution Rows
        |--------------------------------------------------------------------------
        */

        $rows =
            [];


        for (
            $rowNumber = 2;
            $rowNumber <= $highestRow;
            $rowNumber++
        ) {

            $values =
                $sheet->rangeToArray(
                    'A'
                    . $rowNumber
                    . ':'
                    . $highestColumn
                    . $rowNumber,
                    null,
                    true,
                    false
                )[0];


            /*
            |--------------------------------------------------------------------------
            | Ignore Blank Rows
            |--------------------------------------------------------------------------
            */

            if (
                $this->isEmptyRow(
                    $values
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Ignore Totals Rows
            |--------------------------------------------------------------------------
            */

            if (
                $this->looksLikeTotalRow(
                    $values
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Raw Excel Values
            |--------------------------------------------------------------------------
            */

            $rawData =
                [];


            foreach (
                $headerRow
                as $columnIndex => $heading
            ) {

                $heading =
                    trim(
                        (string)
                        $heading
                    );


                if (
                    $heading ===
                    ''
                ) {
                    continue;
                }


                $rawData[
                    $heading
                ] =
                    $values[
                        $columnIndex
                    ]
                    ?? null;
            }


            /*
            |--------------------------------------------------------------------------
            | Normalized Values
            |--------------------------------------------------------------------------
            */

            $normalizedData =
                $this->normalizeRow(
                    $values,
                    $columnMap
                );


            /*
            |--------------------------------------------------------------------------
            | Add Row
            |--------------------------------------------------------------------------
            */

            $rows[] = [
                'row_number' =>
                    $rowNumber,

                'raw_data' =>
                    $rawData,

                'normalized_data' =>
                    $normalizedData,
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Return Parsed Excel
        |--------------------------------------------------------------------------
        */

        return [
            'rows' =>
                $rows,

            'row_count' =>
                count(
                    $rows
                ),

            'headers' =>
                $headerRow,

            'column_map' =>
                $columnMap,
        ];
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
        */

        $map =
            [];


        foreach (
            $this->aliases
            as $logicalField => $aliases
        ) {

            foreach (
                $normalizedHeaders
                as $index => $normalizedHeading
            ) {

                foreach (
                    $aliases
                    as $alias
                ) {

                    if (
                        $normalizedHeading
                        ===
                        $this->normalizeHeading(
                            $alias
                        )
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
                . '.'
            );
        }
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

            'pension_reference_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'pension_reference_number'
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


            /*
            |--------------------------------------------------------------------------
            | Member Details
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
            |
            | The validator maps these to ZWG or USD according to the selected
            | contribution batch currency.
            |
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
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'employee_rate'
                    )
                ),

            'employer_rate' =>
                $this->numberValue(
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
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'usd_employee_rate'
                    )
                ),

            'usd_employer_rate' =>
                $this->numberValue(
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
                $this->numberValue(
                    $this->value(
                        $values,
                        $map,
                        'zwg_employee_rate'
                    )
                ),

            'zwg_employer_rate' =>
                $this->numberValue(
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
            ?? null;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalize Heading
    |--------------------------------------------------------------------------
    |
    | Important:
    |
    | "first_names"
    | "First Names"
    | "first-names"
    |
    | all become:
    |
    | first names
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
        | Remove Repeated Spaces
        |--------------------------------------------------------------------------
        */

        $value =
            preg_replace(
                '/\s+/',
                ' ',
                $value
            );


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
            ||
            trim(
                (string)
                $value
            )
            ===
            ''
        ) {
            return null;
        }


        return trim(
            (string)
            $value
        );
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
            ||
            trim(
                (string)
                $value
            )
            ===
            ''
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


        /*
        |--------------------------------------------------------------------------
        | Clean Formatted Numbers
        |--------------------------------------------------------------------------
        */

        $clean =
            trim(
                (string)
                $value
            );


        /*
        |--------------------------------------------------------------------------
        | Accounting Negative Format
        |--------------------------------------------------------------------------
        |
        | Example:
        |
        | (1,000.00)
        |
        | becomes:
        |
        | -1000.00
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
            str_replace(
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


        if (
            $negative
        ) {
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
    | Date Value
    |--------------------------------------------------------------------------
    */

    private function dateValue(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            trim(
                (string)
                $value
            )
            ===
            ''
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
                !==
                ''
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