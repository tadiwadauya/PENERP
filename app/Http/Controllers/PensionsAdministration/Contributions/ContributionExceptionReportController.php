<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContributionExceptionReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Display Contribution Exceptions
    |--------------------------------------------------------------------------
    */

    public function show(
        ContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
            'uploadedBy',
            'approvedBy',
            'postedBy',
        ]);


        $exceptions =
            $this->buildExceptions(
                $batch
            );


        return view(
            'pensions-administration.contributions.exceptions.show',
            compact(
                'batch',
                'exceptions'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Export Contribution Exceptions To Excel
    |--------------------------------------------------------------------------
    */

    public function excel(
        ContributionImportBatch $batch
    ): BinaryFileResponse {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        $exceptions =
            $this->buildExceptions(
                $batch
            );


        /*
        |--------------------------------------------------------------------------
        | Spreadsheet
        |--------------------------------------------------------------------------
        */

        $spreadsheet =
            new Spreadsheet();


        $sheet =
            $spreadsheet
                ->getActiveSheet();


        $sheet->setTitle(
            'Contribution Exceptions'
        );


        /*
        |--------------------------------------------------------------------------
        | Report Title
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A1',
            'PENERP CONTRIBUTION RATE / CALCULATION EXCEPTION REPORT'
        );


        $sheet->mergeCells(
            'A1:S1'
        );


        $sheet
            ->getStyle(
                'A1'
            )
            ->getFont()
            ->setBold(
                true
            )
            ->setSize(
                14
            );


        $sheet
            ->getStyle(
                'A1'
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        /*
        |--------------------------------------------------------------------------
        | Employer
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A2',
            'Employer'
        );


        $sheet->setCellValue(
            'B2',
            $batch
                ->employer
                ?->name
            ??
            ''
        );


        /*
        |--------------------------------------------------------------------------
        | Period
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A3',
            'Contribution Period'
        );


        $sheet->setCellValue(
            'B3',
            $batch
                ->contributionPeriod
                ?->period_label
            ??
            (
                $batch
                    ->contributionPeriod
                    ?->period_date
                    ?->format('F Y')
                ??
                ''
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Currency
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A4',
            'Currency'
        );


        $sheet->setCellValue(
            'B4',
            strtoupper(
                $batch->currency_code
                ??
                'ZWG'
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Batch
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A5',
            'Batch'
        );


        $sheet->setCellValue(
            'B5',
            '#'
            . $batch->id
        );


        /*
        |--------------------------------------------------------------------------
        | Exception Count
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A6',
            'Exception Rows'
        );


        $sheet->setCellValue(
            'B6',
            count(
                $exceptions
            )
        );


        /*
        |--------------------------------------------------------------------------
        | Headers
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Excel Row',
            'Member Type',
            'PENERP No.',
            'PenAd No.',
            'Staff No.',
            'National ID',
            'Member Name',
            'Basic Pay',
            'Employee Rate Uploaded',
            'Employee Rate Expected',
            'Employee Contribution Uploaded',
            'Employee Contribution Calculated',
            'Employee Variance',
            'Employer Rate Uploaded',
            'Employer Rate Expected',
            'Employer Contribution Uploaded',
            'Employer Contribution Calculated',
            'Employer Variance',
            'Warnings',
        ];


        $headerRow =
            8;


        $sheet->fromArray(
            $headers,
            null,
            'A'
            . $headerRow
        );


        /*
        |--------------------------------------------------------------------------
        | Header Formatting
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle(
                'A8:S8'
            )
            ->getFont()
            ->setBold(
                true
            );


        $sheet
            ->getStyle(
                'A8:S8'
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FFD9EAF7'
            );


        $sheet
            ->getStyle(
                'A8:S8'
            )
            ->getAlignment()
            ->setWrapText(
                true
            );


        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $excelRow =
            9;


        foreach (
            $exceptions
            as $exception
        ) {
            $sheet->fromArray(
                [
                    $exception[
                        'row_number'
                    ],

                    $exception[
                        'member_type'
                    ],

                    $exception[
                        'penerp_member_number'
                    ],

                    $exception[
                        'penad_member_number'
                    ],

                    $exception[
                        'staff_number'
                    ],

                    $exception[
                        'national_id'
                    ],

                    $exception[
                        'member_name'
                    ],

                    $exception[
                        'basic_pay'
                    ],

                    $exception[
                        'employee_rate_uploaded'
                    ],

                    $exception[
                        'employee_rate_expected'
                    ],

                    $exception[
                        'employee_contribution_uploaded'
                    ],

                    $exception[
                        'employee_contribution_calculated'
                    ],

                    $exception[
                        'employee_variance'
                    ],

                    $exception[
                        'employer_rate_uploaded'
                    ],

                    $exception[
                        'employer_rate_expected'
                    ],

                    $exception[
                        'employer_contribution_uploaded'
                    ],

                    $exception[
                        'employer_contribution_calculated'
                    ],

                    $exception[
                        'employer_variance'
                    ],

                    implode(
                        ' | ',
                        $exception[
                            'warnings'
                        ]
                    ),
                ],
                null,
                'A'
                . $excelRow
            );


            $excelRow++;
        }


        /*
        |--------------------------------------------------------------------------
        | Number Formats
        |--------------------------------------------------------------------------
        */

        if (
            $excelRow > 9
        ) {
            $lastDataRow =
                $excelRow
                -
                1;


            $sheet
                ->getStyle(
                    'H9:H'
                    . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );


            $sheet
                ->getStyle(
                    'I9:J'
                    . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '0.00"%"'
                );


            $sheet
                ->getStyle(
                    'K9:M'
                    . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );


            $sheet
                ->getStyle(
                    'N9:O'
                    . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '0.00"%"'
                );


            $sheet
                ->getStyle(
                    'P9:R'
                    . $lastDataRow
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );


            $sheet
                ->getStyle(
                    'S9:S'
                    . $lastDataRow
                )
                ->getAlignment()
                ->setWrapText(
                    true
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Freeze Header
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane(
            'A9'
        );


        /*
        |--------------------------------------------------------------------------
        | Auto Filter
        |--------------------------------------------------------------------------
        */

        $filterEnd =
            max(
                8,
                $excelRow
                -
                1
            );


        $sheet->setAutoFilter(
            'A8:S'
            . $filterEnd
        );


        /*
        |--------------------------------------------------------------------------
        | Column Widths
        |--------------------------------------------------------------------------
        */

        $widths = [
            'A' => 12,
            'B' => 18,
            'C' => 18,
            'D' => 18,
            'E' => 16,
            'F' => 20,
            'G' => 30,
            'H' => 16,
            'I' => 20,
            'J' => 20,
            'K' => 24,
            'L' => 25,
            'M' => 18,
            'N' => 20,
            'O' => 20,
            'P' => 24,
            'Q' => 25,
            'R' => 18,
            'S' => 80,
        ];


        foreach (
            $widths
            as $column => $width
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setWidth(
                    $width
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Output File
        |--------------------------------------------------------------------------
        */

        $directory =
            storage_path(
                'app/tmp/contributions'
            );


        if (
            !is_dir(
                $directory
            )
        ) {
            mkdir(
                $directory,
                0775,
                true
            );
        }


        $employer =
            preg_replace(
                '/[^A-Za-z0-9_\-]/',
                '_',
                $batch
                    ->employer
                    ?->name
                ??
                'Employer'
            );


        $period =
            $batch
                ->contributionPeriod
                ?->period_date
                ?->format(
                    'Y_m'
                )
            ??
            now()->format(
                'Y_m'
            );


        $filename =
            'Contribution_Exceptions_'
            . $employer
            . '_'
            . $period
            . '_Batch_'
            . $batch->id
            . '.xlsx';


        $path =
            $directory
            . DIRECTORY_SEPARATOR
            . $filename;


        $writer =
            new Xlsx(
                $spreadsheet
            );


        $writer->save(
            $path
        );


        $spreadsheet
            ->disconnectWorksheets();


        return response()
            ->download(
                $path,
                $filename
            )
            ->deleteFileAfterSend(
                true
            );
    }


    /*
    |--------------------------------------------------------------------------
    | Build Exception Rows
    |--------------------------------------------------------------------------
    */

    private function buildExceptions(
        ContributionImportBatch $batch
    ): array {
        $currency =
            strtoupper(
                $batch->currency_code
                ??
                'ZWG'
            );


        $prefix =
            strtolower(
                $currency
            );


        $rows =
            ContributionImportRow::query()
                ->with([
                    'matchedMember',
                ])
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'validation_status',
                    'warning'
                )
                ->orderBy(
                    'row_number'
                )
                ->get();


        $exceptions =
            [];


        foreach (
            $rows
            as $row
        ) {
            $warnings =
                $row->warning_messages
                ??
                [];


            if (
                !is_array(
                    $warnings
                )
            ) {
                $warnings =
                    [];
            }


            /*
            |--------------------------------------------------------------------------
            | Only Rate / Contribution Calculation Warnings
            |--------------------------------------------------------------------------
            */

            $contributionWarnings =
                collect(
                    $warnings
                )
                    ->filter(
                        function (
                            $warning
                        ): bool {
                            $warning =
                                strtoupper(
                                    (string)
                                    $warning
                                );


                            return
                                str_contains(
                                    $warning,
                                    'RATE EXCEPTION'
                                )
                                ||
                                str_contains(
                                    $warning,
                                    'CONTRIBUTION EXCEPTION'
                                );
                        }
                    )
                    ->values()
                    ->all();


            if (
                empty(
                    $contributionWarnings
                )
            ) {
                continue;
            }


            $data =
                $row->normalized_data
                ??
                [];


            /*
            |--------------------------------------------------------------------------
            | Basic Pay
            |--------------------------------------------------------------------------
            */

            $basicPay =
                (float) (
                    $data[
                        $prefix
                        . '_basic_pay'
                    ]
                    ??
                    0
                );


            /*
            |--------------------------------------------------------------------------
            | Uploaded Employee Rate
            |--------------------------------------------------------------------------
            */

            $employeeRateUploaded =
                $this->normaliseRate(
                    $data[
                        $prefix
                        . '_employee_rate'
                    ]
                    ??
                    0
                );


            /*
            |--------------------------------------------------------------------------
            | Expected Employee Rate
            |--------------------------------------------------------------------------
            |
            | New members = mandatory 6%.
            |
            | Existing members retain their uploaded historical rate as long
            | as it lies between 5% and 6%.
            |
            | If it falls outside the permitted range we still display the
            | uploaded rate because this report is showing the actual system
            | calculation that generated the warning.
            |
            */

            $employeeRateExpected =
                $row->is_new_member
                    ? 6.00
                    : $employeeRateUploaded;


            /*
            |--------------------------------------------------------------------------
            | System Employee Contribution
            |--------------------------------------------------------------------------
            */

            $employeeCalculated =
                round(
                    $basicPay
                    *
                    (
                        $employeeRateExpected
                        /
                        100
                    ),
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | Uploaded Employee Contribution
            |--------------------------------------------------------------------------
            */

            $employeeUploaded =
                (float) (
                    $data[
                        $prefix
                        . '_employee_contribution'
                    ]
                    ??
                    0
                );


            /*
            |--------------------------------------------------------------------------
            | Employee Variance
            |--------------------------------------------------------------------------
            |
            | System Calculated - Schedule
            |
            */

            $employeeVariance =
                $employeeCalculated
                -
                $employeeUploaded;


            /*
            |--------------------------------------------------------------------------
            | Employer Rate
            |--------------------------------------------------------------------------
            */

            $employerRateUploaded =
                $this->normaliseRate(
                    $data[
                        $prefix
                        . '_employer_rate'
                    ]
                    ??
                    0
                );


            $employerRateExpected =
                17.30;


            /*
            |--------------------------------------------------------------------------
            | System Employer Contribution
            |--------------------------------------------------------------------------
            */

            $employerCalculated =
                round(
                    $basicPay
                    *
                    0.173,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | Uploaded Employer Contribution
            |--------------------------------------------------------------------------
            */

            $employerUploaded =
                (float) (
                    $data[
                        $prefix
                        . '_employer_contribution'
                    ]
                    ??
                    0
                );


            /*
            |--------------------------------------------------------------------------
            | Employer Variance
            |--------------------------------------------------------------------------
            */

            $employerVariance =
                $employerCalculated
                -
                $employerUploaded;


            /*
            |--------------------------------------------------------------------------
            | Member Number
            |--------------------------------------------------------------------------
            */

            $penerpMemberNumber =
                $row
                    ->matchedMember
                    ?->member_number
                ??
                $data[
                    'penerp_member_number'
                ]
                ??
                '';


            $penadMemberNumber =
                $row
                    ->matchedMember
                    ?->penad_member_number
                ??
                $data[
                    'penad_member_number'
                ]
                ??
                $data[
                    'pension_reference_number'
                ]
                ??
                '';


            /*
            |--------------------------------------------------------------------------
            | Member Name
            |--------------------------------------------------------------------------
            */

            $memberName =
                trim(
                    (
                        $data[
                            'surname'
                        ]
                        ??
                        ''
                    )
                    . ' '
                    . (
                        $data[
                            'first_names'
                        ]
                        ??
                        ''
                    )
                    . ' '
                    . (
                        $data[
                            'other_names'
                        ]
                        ??
                        ''
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | Exception
            |--------------------------------------------------------------------------
            */

            $exceptions[] = [
                'row_number' =>
                    $row->row_number,

                'member_type' =>
                    $row->is_new_member
                        ? 'Proposed New Member'
                        : 'Existing Member',

                'penerp_member_number' =>
                    $penerpMemberNumber,

                'penad_member_number' =>
                    $penadMemberNumber,

                'staff_number' =>
                    $data[
                        'staff_number'
                    ]
                    ??
                    '',

                'national_id' =>
                    $data[
                        'national_id'
                    ]
                    ??
                    '',

                'member_name' =>
                    $memberName,

                'basic_pay' =>
                    $basicPay,

                'employee_rate_uploaded' =>
                    $employeeRateUploaded,

                'employee_rate_expected' =>
                    $employeeRateExpected,

                'employee_contribution_uploaded' =>
                    $employeeUploaded,

                'employee_contribution_calculated' =>
                    $employeeCalculated,

                'employee_variance' =>
                    $employeeVariance,

                'employer_rate_uploaded' =>
                    $employerRateUploaded,

                'employer_rate_expected' =>
                    $employerRateExpected,

                'employer_contribution_uploaded' =>
                    $employerUploaded,

                'employer_contribution_calculated' =>
                    $employerCalculated,

                'employer_variance' =>
                    $employerVariance,

                'employee_avc' =>
                    (float) (
                        $data[
                            $prefix
                            . '_employee_avc'
                        ]
                        ??
                        0
                    ),

                'employer_avc' =>
                    (float) (
                        $data[
                            $prefix
                            . '_employer_avc'
                        ]
                        ??
                        0
                    ),

                'warnings' =>
                    $contributionWarnings,
            ];
        }


        return $exceptions;
    }


    /*
    |--------------------------------------------------------------------------
    | Normalise Rate
    |--------------------------------------------------------------------------
    */

    private function normaliseRate(
        mixed $value
    ): float {
        $rate =
            (float) (
                $value
                ??
                0
            );


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
    | Permission Enforcement
    |--------------------------------------------------------------------------
    */

    private function ensurePermission(
        string $permission
    ): void {
        $user =
            auth()->user();


        abort_if(
            !$user,
            401,
            'Unauthenticated.'
        );


        if (
            $user->is_system_administrator
        ) {
            return;
        }


        abort_unless(
            $user->can(
                $permission
            ),
            403,
            'You do not have permission to perform this action.'
        );
    }
}