<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\PensionsAdministration\Contributions\ContributionReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContributionReconciliationController extends Controller
{
    public function __construct(
        private readonly ContributionReconciliationService $reconciliationService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Screen Report
    |--------------------------------------------------------------------------
    */

    public function show(
        ContributionImportBatch $batch
    ): View {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $report =
            $this
                ->reconciliationService
                ->build(
                    $batch
                );


        return view(
            'pensions-administration.contributions.reconciliation.show',
            compact(
                'report'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(
        ContributionImportBatch $batch
    ): Response {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $report =
            $this
                ->reconciliationService
                ->build(
                    $batch
                );


        $filename =
            $this->filename(
                $report,
                'pdf'
            );


        $pdf =
            Pdf::loadView(
                'pensions-administration.contributions.reconciliation.pdf',
                compact(
                    'report'
                )
            )
                ->setPaper(
                    'a4',
                    'landscape'
                );


        return $pdf->stream(
            $filename
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Excel
    |--------------------------------------------------------------------------
    */

    public function excel(
        ContributionImportBatch $batch
    ): BinaryFileResponse {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $report =
            $this
                ->reconciliationService
                ->build(
                    $batch
                );


        $spreadsheet =
            new Spreadsheet();


        /*
        |--------------------------------------------------------------------------
        | Summary Sheet
        |--------------------------------------------------------------------------
        */

        $summarySheet =
            $spreadsheet
                ->getActiveSheet();


        $summarySheet->setTitle(
            'Reconciliation'
        );


        $currency =
            $report[
                'currency'
            ];


        $period =
            $report[
                'current_period'
            ];


        $previousPeriod =
            $report[
                'previous_period'
            ];


        $employer =
            $report[
                'employer'
            ];


        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */

        $summarySheet->setCellValue(
            'A1',
            'LOCAL AUTHORITIES PENSION FUND'
        );


        $summarySheet->mergeCells(
            'A1:D1'
        );


        $summarySheet->setCellValue(
            'A2',
            strtoupper(
                $employer?->name
                ??
                'EMPLOYER'
            )
        );


        $summarySheet->mergeCells(
            'A2:D2'
        );


        $summarySheet->setCellValue(
            'A3',
            'MONTHLY CONTRIBUTION RECONCILIATION'
        );


        $summarySheet->mergeCells(
            'A3:D3'
        );


        $summarySheet->setCellValue(
            'A4',
            'Currency'
        );


        $summarySheet->setCellValue(
            'B4',
            $currency
        );


        $summarySheet->setCellValue(
            'C4',
            'Batch'
        );


        $summarySheet->setCellValue(
            'D4',
            '#'
            . $report[
                'batch'
            ]->id
        );


        /*
        |--------------------------------------------------------------------------
        | Membership
        |--------------------------------------------------------------------------
        */

        $summarySheet->setCellValue(
            'A6',
            'MONTHLY MEMBERSHIP RECONCILIATION'
        );


        $summarySheet->mergeCells(
            'A6:D6'
        );


        $membershipRows = [
            [
                'Membership as at '
                . (
                    $previousPeriod
                        ?->period_date
                        ?->format('d F Y')
                    ??
                    'Previous Period'
                ),
                $report[
                    'membership'
                ][
                    'previous'
                ],
            ],

            [
                'Add New Members',
                $report[
                    'membership'
                ][
                    'new_members'
                ],
            ],

            [
                'Add Reinstatements',
                $report[
                    'membership'
                ][
                    'reinstatements'
                ],
            ],

            [
                'Less Exits / Suspended / Nil Contributors',
                $report[
                    'membership'
                ][
                    'less_exits_suspended_nil'
                ],
            ],

            [
                'Membership as at '
                . $period
                    ->period_date
                    ->format('d F Y'),
                $report[
                    'membership'
                ][
                    'current'
                ],
            ],
        ];


        $row =
            7;


        foreach (
            $membershipRows
            as $membershipRow
        ) {
            $summarySheet->setCellValue(
                'A'
                . $row,
                $membershipRow[
                    0
                ]
            );


            $summarySheet->setCellValue(
                'B'
                . $row,
                $membershipRow[
                    1
                ]
            );


            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | Traditional Contribution Movement Reconciliation
        |--------------------------------------------------------------------------
        */

        $row +=
            1;


        $summarySheet->setCellValue(
            'A'
            . $row,
            'MONTHLY CONTRIBUTION MOVEMENT RECONCILIATION'
        );


        $summarySheet->mergeCells(
            'A'
            . $row
            . ':D'
            . $row
        );


        $row++;


        $summarySheet->fromArray(
            [
                [
                    'Description',
                    'Normal Contributions',
                    'AVC',
                    'Total',
                ],
            ],
            null,
            'A'
            . $row
        );


        $headerMovementRow =
            $row;


        $row++;


        $contributionRows = [
            [
                'Contributions Due as at '
                . (
                    $previousPeriod
                        ?->period_date
                        ?->format('d F Y')
                    ??
                    'Previous Period'
                ),

                $report[
                    'contributions'
                ][
                    'previous_normal'
                ],

                $report[
                    'contributions'
                ][
                    'previous_avc'
                ],

                $report[
                    'contributions'
                ][
                    'previous_total'
                ],
            ],

            [
                'Add Contributions for New Members',

                $report[
                    'contributions'
                ][
                    'new_members_normal'
                ],

                $report[
                    'contributions'
                ][
                    'new_members_avc'
                ],

                $report[
                    'contributions'
                ][
                    'new_members_total'
                ],
            ],

            [
                'Add Contributions for Reinstatements',

                $report[
                    'contributions'
                ][
                    'reinstatements_normal'
                ],

                $report[
                    'contributions'
                ][
                    'reinstatements_avc'
                ],

                $report[
                    'contributions'
                ][
                    'reinstatements_total'
                ],
            ],

            [
                'Add Increase / Decrease on Contributions',

                $report[
                    'contributions'
                ][
                    'increase_decrease_normal'
                ],

                $report[
                    'contributions'
                ][
                    'increase_decrease_avc'
                ],

                $report[
                    'contributions'
                ][
                    'increase_decrease_total'
                ],
            ],

            [
                'Add Differences on Contributions',

                $report[
                    'contributions'
                ][
                    'differences_normal'
                ],

                $report[
                    'contributions'
                ][
                    'differences_avc'
                ],

                $report[
                    'contributions'
                ][
                    'differences_total'
                ],
            ],

            [
                'Less Contributions for Exits / Suspended / Nil Contributors',

                -1
                *
                $report[
                    'contributions'
                ][
                    'less_nil_normal'
                ],

                -1
                *
                $report[
                    'contributions'
                ][
                    'less_nil_avc'
                ],

                -1
                *
                $report[
                    'contributions'
                ][
                    'less_nil_total'
                ],
            ],

            [
                'Total Contributions Due as at '
                . $period
                    ->period_date
                    ->format('d F Y'),

                $report[
                    'contributions'
                ][
                    'normal_due'
                ],

                $report[
                    'contributions'
                ][
                    'avc_due'
                ],

                $report[
                    'contributions'
                ][
                    'total_due'
                ],
            ],

            [
                'Total Contributions as per Schedule',

                $report[
                    'contributions'
                ][
                    'schedule_normal'
                ],

                $report[
                    'contributions'
                ][
                    'schedule_avc'
                ],

                $report[
                    'contributions'
                ][
                    'schedule_total'
                ],
            ],

            [
                'Movement Reconciliation Variance',

                $report[
                    'contributions'
                ][
                    'normal_variance'
                ],

                $report[
                    'contributions'
                ][
                    'avc_variance'
                ],

                $report[
                    'contributions'
                ][
                    'variance'
                ],
            ],
        ];


        foreach (
            $contributionRows
            as $contributionRow
        ) {
            $summarySheet->fromArray(
                [
                    $contributionRow,
                ],
                null,
                'A'
                . $row
            );


            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | System Calculation Reconciliation
        |--------------------------------------------------------------------------
        */

        $row +=
            1;


        $summarySheet->setCellValue(
            'A'
            . $row,
            'PENERP SYSTEM CALCULATION VS UPLOADED SCHEDULE'
        );


        $summarySheet->mergeCells(
            'A'
            . $row
            . ':D'
            . $row
        );


        $row++;


        $summarySheet->fromArray(
            [
                [
                    'Description',
                    'System Calculated',
                    'Uploaded Schedule',
                    'Variance',
                ],
            ],
            null,
            'A'
            . $row
        );


        $calculationHeaderRow =
            $row;


        $row++;


        $calculationRows = [
            [
                'Employee Contribution',

                $report[
                    'calculation'
                ][
                    'employee_contribution'
                ],

                $report[
                    'schedule'
                ][
                    'employee_contribution'
                ],

                $report[
                    'calculation'
                ][
                    'employee_variance'
                ],
            ],

            [
                'Employer Contribution',

                $report[
                    'calculation'
                ][
                    'employer_contribution'
                ],

                $report[
                    'schedule'
                ][
                    'employer_contribution'
                ],

                $report[
                    'calculation'
                ][
                    'employer_variance'
                ],
            ],

            [
                'Normal Contributions',

                $report[
                    'calculation'
                ][
                    'normal_contributions'
                ],

                $report[
                    'schedule'
                ][
                    'normal_contributions'
                ],

                $report[
                    'calculation'
                ][
                    'normal_variance'
                ],
            ],

            [
                'Employee AVC',

                $report[
                    'calculation'
                ][
                    'employee_avc'
                ],

                $report[
                    'schedule'
                ][
                    'employee_avc'
                ],

                0,
            ],

            [
                'Employer AVC',

                $report[
                    'calculation'
                ][
                    'employer_avc'
                ],

                $report[
                    'schedule'
                ][
                    'employer_avc'
                ],

                0,
            ],

            [
                'Total AVC',

                $report[
                    'calculation'
                ][
                    'avc'
                ],

                $report[
                    'schedule'
                ][
                    'avc'
                ],

                $report[
                    'calculation'
                ][
                    'avc_variance'
                ],
            ],

            [
                'GRAND TOTAL',

                $report[
                    'calculation'
                ][
                    'total_expected'
                ],

                $report[
                    'schedule'
                ][
                    'total_expected'
                ],

                $report[
                    'calculation'
                ][
                    'variance'
                ],
            ],
        ];


        foreach (
            $calculationRows
            as $calculationRow
        ) {
            $summarySheet->fromArray(
                [
                    $calculationRow,
                ],
                null,
                'A'
                . $row
            );


            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | Formatting Summary
        |--------------------------------------------------------------------------
        */

        foreach (
            [
                'A1:D1',
                'A2:D2',
                'A3:D3',
                'A6:D6',
                'A'
                . (
                    $headerMovementRow
                    -
                    1
                )
                . ':D'
                . (
                    $headerMovementRow
                    -
                    1
                ),
                'A'
                . (
                    $calculationHeaderRow
                    -
                    1
                )
                . ':D'
                . (
                    $calculationHeaderRow
                    -
                    1
                ),
            ]
            as $titleRange
        ) {
            $summarySheet
                ->getStyle(
                    $titleRange
                )
                ->getFont()
                ->setBold(
                    true
                );


            $summarySheet
                ->getStyle(
                    $titleRange
                )
                ->getAlignment()
                ->setHorizontal(
                    Alignment::HORIZONTAL_CENTER
                );
        }


        foreach (
            [
                'A'
                . $headerMovementRow
                . ':D'
                . $headerMovementRow,

                'A'
                . $calculationHeaderRow
                . ':D'
                . $calculationHeaderRow,
            ]
            as $headerRange
        ) {
            $summarySheet
                ->getStyle(
                    $headerRange
                )
                ->getFont()
                ->setBold(
                    true
                );


            $summarySheet
                ->getStyle(
                    $headerRange
                )
                ->getFill()
                ->setFillType(
                    Fill::FILL_SOLID
                )
                ->getStartColor()
                ->setARGB(
                    'FFD9EAF7'
                );
        }


        $summarySheet
            ->getColumnDimension('A')
            ->setWidth(58);


        foreach (
            [
                'B',
                'C',
                'D',
            ]
            as $column
        ) {
            $summarySheet
                ->getColumnDimension(
                    $column
                )
                ->setWidth(
                    22
                );
        }


        $summarySheet
            ->getStyle(
                'B1:D'
                . $row
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );


        /*
        |--------------------------------------------------------------------------
        | Detail Sheet
        |--------------------------------------------------------------------------
        */

        $detailSheet =
            $spreadsheet
                ->createSheet();


        $detailSheet->setTitle(
            'Member Calculations'
        );


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
            'Employee Contribution Schedule',
            'Employee Contribution System',
            'Employee Variance',
            'Employer Rate Uploaded',
            'Employer Rate Expected',
            'Employer Contribution Schedule',
            'Employer Contribution System',
            'Employer Variance',
            'Employee AVC',
            'Employer AVC',
            'Schedule Total',
            'System Total',
            'Total Variance',
        ];


        $detailSheet->fromArray(
            [
                $headers,
            ],
            null,
            'A1'
        );


        $detailSheet
            ->getStyle(
                'A1:W1'
            )
            ->getFont()
            ->setBold(
                true
            );


        $detailSheet
            ->getStyle(
                'A1:W1'
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FFD9EAF7'
            );


        $detailRow =
            2;


        foreach (
            $report[
                'calculation_rows'
            ]
            as $memberRow
        ) {
            $detailSheet->fromArray(
                [[
                    $memberRow[
                        'row_number'
                    ],

                    $memberRow[
                        'member_type'
                    ],

                    $memberRow[
                        'penerp_member_number'
                    ],

                    $memberRow[
                        'penad_member_number'
                    ],

                    $memberRow[
                        'staff_number'
                    ],

                    $memberRow[
                        'national_id'
                    ],

                    $memberRow[
                        'member_name'
                    ],

                    $memberRow[
                        'basic_pay'
                    ],

                    $memberRow[
                        'employee_rate_uploaded'
                    ],

                    $memberRow[
                        'employee_rate_expected'
                    ],

                    $memberRow[
                        'employee_schedule'
                    ],

                    $memberRow[
                        'employee_system'
                    ],

                    $memberRow[
                        'employee_variance'
                    ],

                    $memberRow[
                        'employer_rate_uploaded'
                    ],

                    $memberRow[
                        'employer_rate_expected'
                    ],

                    $memberRow[
                        'employer_schedule'
                    ],

                    $memberRow[
                        'employer_system'
                    ],

                    $memberRow[
                        'employer_variance'
                    ],

                    $memberRow[
                        'employee_avc'
                    ],

                    $memberRow[
                        'employer_avc'
                    ],

                    $memberRow[
                        'schedule_total'
                    ],

                    $memberRow[
                        'system_total'
                    ],

                    $memberRow[
                        'variance'
                    ],
                ]],
                null,
                'A'
                . $detailRow
            );


            $detailRow++;
        }


        $detailSheet->freezePane(
            'A2'
        );


        $detailSheet->setAutoFilter(
            'A1:W'
            . max(
                1,
                $detailRow
                -
                1
            )
        );


        foreach (
            range(
                'A',
                'W'
            )
            as $column
        ) {
            $detailSheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(
                    true
                );
        }


        if (
            $detailRow > 2
        ) {
            $detailSheet
                ->getStyle(
                    'H2:W'
                    . (
                        $detailRow
                        -
                        1
                    )
                )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $directory =
            storage_path(
                'app/tmp/contributions'
            );


        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0775,
                true
            );
        }


        $filename =
            $this->filename(
                $report,
                'xlsx'
            );


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
    | Filename
    |--------------------------------------------------------------------------
    */

    private function filename(
        array $report,
        string $extension
    ): string {
        $employer =
            preg_replace(
                '/[^A-Za-z0-9_\-]/',
                '_',
                $report[
                    'employer'
                ]
                    ?->name
                ??
                'Employer'
            );


        $period =
            $report[
                'current_period'
            ]
                ?->period_date
                ?->format(
                    'Y_m_d'
                )
            ??
            now()->format(
                'Y_m_d'
            );


        return
            'Monthly_Contribution_Reconciliation_'
            . $employer
            . '_'
            . $period
            . '_Batch_'
            . $report[
                'batch'
            ]->id
            . '.'
            . $extension;
    }


    /*
    |--------------------------------------------------------------------------
    | Permission
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