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
    | We accept common variations because employer schedules may not always
    | use exactly the same spelling/capitalisation.
    |
    */

    private array $aliases = [

        'employer_number' => [
            'employer number',
            'employer no',
            'employer no.',
            'employer code',
        ],

        'scheme_code' => [
            'scheme code',
            'scheme',
            'fund code',
        ],

        'due_date' => [
            'due date',
            'contribution date',
            'period date',
        ],

        'surname' => [
            'surname',
            'last name',
        ],

        'first_names' => [
            'first name',
            'first names',
            'firstname',
            'forename',
            'forenames',
        ],

        'other_names' => [
            'other names',
            'middle names',
            'other name',
        ],

        'date_of_birth' => [
            'date of birth',
            'dob',
            'birth date',
        ],

        'gender' => [
            'gender',
            'sex',
        ],

        'national_id' => [
            'national registration number',
            'national id',
            'national id no',
            'national id number',
            'id number',
            'nationalidno',
        ],

        'date_joined_fund' => [
            'date joined fund',
            'fund join date',
            'date joined scheme',
        ],

        'date_joined_employer' => [
            'date joined employer',
            'employment date',
            'date employed',
        ],

        'staff_number' => [
            'employee code or works number',
            'employee code',
            'works number',
            'staff number',
            'staff no',
            'staff no.',
            'employee number',
        ],

        'pension_reference_number' => [
            'pension reference number',
            'pension reference',
            'member number',
            'member no',
            'member no.',
            'penad number',
            'penad member number',
        ],

        'penerp_member_number' => [
            'penerp number',
            'penerp member number',
            'penerp no',
        ],

        'fundworx_member_number' => [
            'fundworx number',
            'fundworx member number',
            'fundworx no',
        ],

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
            'payment flag',
            'payment status',
        ],

        'usd_basic_pay' => [
            'usd basic pay',
            'usd salary',
            'usd pensionable salary',
        ],

        'usd_employee_rate' => [
            'usd employee rate',
            'usd member rate',
        ],

        'usd_employer_rate' => [
            'usd employer rate',
        ],

        'usd_employee_contribution' => [
            'usd employee contribution',
            'usd member contribution',
        ],

        'usd_employer_contribution' => [
            'usd employer contribution',
        ],

        'usd_employee_avc' => [
            'usd employee voluntary contribution',
            'usd employee avc',
            'usd member avc',
        ],

        'usd_employer_avc' => [
            'usd employer voluntary contribution',
            'usd employer avc',
        ],

        'usd_employee_arrear' => [
            'usd employee arrear contribution',
            'usd member arrear contribution',
        ],

        'usd_employer_arrear' => [
            'usd employer arrear contribution',
        ],

        'usd_employee_transfer_in' => [
            'usd employee transfer in',
            'usd member transfer in',
        ],

        'usd_employer_transfer_in' => [
            'usd employer transfer in',
        ],

        'usd_employee_late_interest' => [
            'usd employee late payment interest',
        ],

        'usd_employer_late_interest' => [
            'usd employer late payment interest',
        ],

        'zwg_basic_pay' => [
            'zwg basic pay',
            'zwg salary',
            'zwg pensionable salary',
        ],

        'zwg_employee_rate' => [
            'zwg employee rate',
            'zwg member rate',
        ],

        'zwg_employer_rate' => [
            'zwg employer rate',
        ],

        'zwg_employee_contribution' => [
            'zwg employee contribution',
            'zwg member contribution',
        ],

        'zwg_employer_contribution' => [
            'zwg employer contribution',
        ],

        'zwg_employee_avc' => [
            'zwg employee voluntary contribution',
            'zwg employee avc',
            'zwg member avc',
        ],

        'zwg_employer_avc' => [
            'zwg employer voluntary contribution',
            'zwg employer avc',
        ],

        'zwg_employee_arrear' => [
            'zwg employee arrear contribution',
        ],

        'zwg_employer_arrear' => [
            'zwg employer arrear contribution',
        ],

        'zwg_employee_transfer_in' => [
            'zwg employee transfer in',
        ],

        'zwg_employer_transfer_in' => [
            'zwg employer transfer in',
        ],

        'zwg_employee_late_interest' => [
            'zwg employee late payment interest',
        ],

        'zwg_employer_late_interest' => [
            'zwg employer late payment interest',
        ],

        'comments' => [
            'comments',
            'comment',
            'remarks',
            'remark',
        ],
    ];


    /*
    |--------------------------------------------------------------------------
    | Mandatory Logical Fields
    |--------------------------------------------------------------------------
    */

    private array $requiredFields = [
        'surname',
        'first_names',

        'staff_number',

        'usd_employee_contribution',
        'usd_employer_contribution',
    ];


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


        $spreadsheet =
            IOFactory::load(
                $path
            );


        $sheet =
            $spreadsheet
                ->getActiveSheet();


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
                'The contribution Excel file does not contain contribution rows.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Headers
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


        $columnMap =
            $this->buildColumnMap(
                $headerRow
            );


        $this->validateRequiredColumns(
            $columnMap
        );


        /*
        |--------------------------------------------------------------------------
        | Rows
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


            if (
                $this->isEmptyRow(
                    $values
                )
            ) {
                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | Ignore Total Rows
            |--------------------------------------------------------------------------
            */

            if (
                $this->looksLikeTotalRow(
                    $values
                )
            ) {
                continue;
            }


            $rawData =
                [];


            foreach (
                $headerRow
                as $columnIndex => $heading
            ) {

                $heading =
                    trim(
                        (string) $heading
                    );


                if (
                    $heading === ''
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


            $normalized =
                $this->normalizeRow(
                    $values,
                    $columnMap
                );


            $rows[] = [
                'row_number' =>
                    $rowNumber,

                'raw_data' =>
                    $rawData,

                'normalized_data' =>
                    $normalized,
            ];
        }


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
    | Normalise Row
    |--------------------------------------------------------------------------
    */

    private function normalizeRow(
        array $values,
        array $map
    ): array {
        return [
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

            'national_id' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'national_id'
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

            'staff_number' =>
                $this->stringValue(
                    $this->value(
                        $values,
                        $map,
                        'staff_number'
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
            | USD
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
            | ZWG
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
            $map[$field]
        ]
            ?? null;
    }


    private function normalizeHeading(
        mixed $value
    ): string {
        $value =
            strtolower(
                trim(
                    (string) $value
                )
            );


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


    private function stringValue(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            trim(
                (string) $value
            )
            === ''
        ) {
            return null;
        }


        return trim(
            (string) $value
        );
    }


    private function numberValue(
        mixed $value
    ): float {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return 0.0;
        }


        if (
            is_numeric(
                $value
            )
        ) {
            return (float) $value;
        }


        $clean =
            str_replace(
                [
                    ',',
                    '$',
                    ' ',
                ],
                '',
                (string) $value
            );


        if (
            str_starts_with(
                $clean,
                '('
            )
            &&
            str_ends_with(
                $clean,
                ')'
            )
        ) {
            $clean =
                '-'
                . trim(
                    $clean,
                    '()'
                );
        }


        return is_numeric(
            $clean
        )
            ? (float) $clean
            : 0.0;
    }


    private function dateValue(
        mixed $value
    ): ?string {
        if (
            $value === null
            ||
            $value === ''
        ) {
            return null;
        }


        try {

            if (
                is_numeric(
                    $value
                )
            ) {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        $value
                    )
                )->toDateString();
            }


            return Carbon::parse(
                (string) $value
            )->toDateString();

        } catch (Throwable) {

            return null;
        }
    }


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
                    (string) $value
                )
                !== ''
            ) {
                return false;
            }
        }


        return true;
    }


    private function looksLikeTotalRow(
        array $values
    ): bool {
        foreach (
            array_slice(
                $values,
                0,
                5
            )
            as $value
        ) {

            if (
                strtoupper(
                    trim(
                        (string) $value
                    )
                )
                ===
                'TOTAL'
            ) {
                return true;
            }
        }


        return false;
    }
}