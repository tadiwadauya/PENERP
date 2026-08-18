<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Services\PensionsAdministration\Contributions\ContributionReconciliationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ContributionReconciliationController extends Controller
{
    public function __construct(
        private readonly ContributionReconciliationService $reconciliationService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Display Reconciliation
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
    | Generate PDF
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


        $employerName =
            $report[
                'employer'
            ]
                ?->name
            ??
            'Employer';


        $period =
            $report[
                'current_period'
            ];


        $periodText =
            $period
                ?->period_date
                ?->format(
                    'Y_m_d'
                )
            ??
            now()->format(
                'Y_m_d'
            );


        $filename =
            'Monthly_Contribution_Reconciliation_'
            . preg_replace(
                '/[^A-Za-z0-9_\-]/',
                '_',
                $employerName
            )
            . '_'
            . $periodText
            . '.pdf';


        $pdf =
            Pdf::loadView(
                'pensions-administration.contributions.reconciliation.pdf',
                compact(
                    'report'
                )
            )
                ->setPaper(
                    'a4',
                    'portrait'
                );


        return $pdf->stream(
            $filename
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Download PDF
    |--------------------------------------------------------------------------
    */

    public function downloadPdf(
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


        $employerName =
            $report[
                'employer'
            ]
                ?->name
            ??
            'Employer';


        $period =
            $report[
                'current_period'
            ];


        $periodText =
            $period
                ?->period_date
                ?->format(
                    'Y_m_d'
                )
            ??
            now()->format(
                'Y_m_d'
            );


        $filename =
            'Monthly_Contribution_Reconciliation_'
            . preg_replace(
                '/[^A-Za-z0-9_\-]/',
                '_',
                $employerName
            )
            . '_'
            . $periodText
            . '.pdf';


        $pdf =
            Pdf::loadView(
                'pensions-administration.contributions.reconciliation.pdf',
                compact(
                    'report'
                )
            )
                ->setPaper(
                    'a4',
                    'portrait'
                );


        return $pdf->download(
            $filename
        );
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