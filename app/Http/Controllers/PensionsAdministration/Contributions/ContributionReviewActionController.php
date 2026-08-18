<?php

namespace App\Http\Controllers\PensionsAdministration\Contributions;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Contributions\ContributionImportBatch;
use App\Models\PensionsAdministration\Contributions\ContributionImportRow;
use App\Models\PensionsAdministration\Contributions\ContributionPeriodMemberStatus;
use App\Notifications\PensionsAdministration\Contributions\ContributionBatchRejected;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ContributionReviewActionController extends Controller
{
    public function __construct(
        private readonly AuditService $auditService
    ) {
    }


    /*
    |--------------------------------------------------------------------------
    | Export New Members
    |--------------------------------------------------------------------------
    */

    public function exportNewMembers(
        ContributionImportBatch $batch
    ): BinaryFileResponse {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        $rows =
            ContributionImportRow::query()
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->where(
                    'is_new_member',
                    true
                )
                ->orderBy(
                    'row_number'
                )
                ->get();


        $spreadsheet =
            new Spreadsheet();


        $sheet =
            $spreadsheet
                ->getActiveSheet();


        $sheet->setTitle(
            'New Members'
        );


        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Excel Row',
            'PENERP Member Number',
            'PenAd Member Number',
            'Fundworx Member Number',
            'Staff Number',
            'National ID',
            'Surname',
            'First Names',
            'Other Names',
            'Date of Birth',
            'Date Joined Fund',
            'Date Joined Employer',
            'Gender',
            'Employer',
            'Validation Status',
            'Warnings',
        ];


        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );


        $sheet
            ->getStyle(
                'A1:P1'
            )
            ->getFont()
            ->setBold(
                true
            );


        $sheet
            ->getStyle(
                'A1:P1'
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FF1F4E78'
            );


        $sheet
            ->getStyle(
                'A1:P1'
            )
            ->getFont()
            ->getColor()
            ->setARGB(
                'FFFFFFFF'
            );


        /*
        |--------------------------------------------------------------------------
        | Rows
        |--------------------------------------------------------------------------
        */

        $excelRow =
            2;


        foreach (
            $rows
            as $row
        ) {
            $data =
                $row->normalized_data
                ?? [];


            $warnings =
                $row->warning_messages
                ?? [];


            if (
                !is_array(
                    $warnings
                )
            ) {
                $warnings =
                    [];
            }


            $sheet->fromArray(
                [
                    $row->row_number,

                    $data[
                        'penerp_member_number'
                    ]
                    ?? '',

                    $data[
                        'penad_member_number'
                    ]
                    ??
                    $data[
                        'pension_reference_number'
                    ]
                    ??
                    '',

                    $data[
                        'fundworx_member_number'
                    ]
                    ?? '',

                    $data[
                        'staff_number'
                    ]
                    ?? '',

                    $data[
                        'national_id'
                    ]
                    ?? '',

                    $data[
                        'surname'
                    ]
                    ?? '',

                    $data[
                        'first_names'
                    ]
                    ?? '',

                    $data[
                        'other_names'
                    ]
                    ?? '',

                    $data[
                        'date_of_birth'
                    ]
                    ?? '',

                    $data[
                        'date_joined_fund'
                    ]
                    ?? '',

                    $data[
                        'date_joined_employer'
                    ]
                    ?? '',

                    $data[
                        'gender'
                    ]
                    ?? '',

                    $batch
                        ->employer
                        ?->name
                    ?? '',

                    $row
                        ->validation_status,

                    implode(
                        ' | ',
                        $warnings
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
        | Column Widths
        |--------------------------------------------------------------------------
        */

        foreach (
            range(
                'A',
                'P'
            )
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(
                    true
                );
        }


        $sheet->freezePane(
            'A2'
        );


        /*
        |--------------------------------------------------------------------------
        | Save Temporary File
        |--------------------------------------------------------------------------
        */

        $filename =
            'new_members_batch_'
            . $batch->id
            . '_'
            . now()->format(
                'Ymd_His'
            )
            . '.xlsx';


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
    | Export Nil Contributors
    |--------------------------------------------------------------------------
    */

    public function exportNilContributors(
        ContributionImportBatch $batch
    ): BinaryFileResponse {
        $this->ensurePermission(
            'contributions.reports.view'
        );


        $batch->load([
            'employer',
            'contributionPeriod',
        ]);


        $statuses =
            ContributionPeriodMemberStatus::query()
                ->with([
                    'member.currentEmployment.employer',
                ])
                ->where(
                    'contribution_period_id',
                    $batch->contribution_period_id
                )
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->orderBy(
                    'member_id'
                )
                ->get();


        $spreadsheet =
            new Spreadsheet();


        $sheet =
            $spreadsheet
                ->getActiveSheet();


        $sheet->setTitle(
            'Nil Contributors'
        );


        $headers = [
            'PENERP Member Number',
            'PenAd Member Number',
            'Fundworx Member Number',
            'Staff Number',
            'Surname',
            'First Names',
            'National ID',
            'Employer',
            'Period',
            'Status',
            'Reason',
        ];


        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );


        $sheet
            ->getStyle(
                'A1:K1'
            )
            ->getFont()
            ->setBold(
                true
            );


        $sheet
            ->getStyle(
                'A1:K1'
            )
            ->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB(
                'FFF4B183'
            );


        $excelRow =
            2;


        foreach (
            $statuses
            as $status
        ) {
            $member =
                $status->member;


            $employment =
                $member
                    ?->currentEmployment;


            $sheet->fromArray(
                [
                    $member
                        ?->member_number
                    ?? '',

                    $member
                        ?->penad_member_number
                    ?? '',

                    $member
                        ?->fundworx_member_number
                    ?? '',

                    $employment
                        ?->staff_number
                    ?? '',

                    $member
                        ?->surname
                    ?? '',

                    $member
                        ?->first_names
                    ?? '',

                    $member
                        ?->national_id
                    ?? '',

                    $employment
                        ?->employer
                        ?->name
                    ??
                    $batch
                        ->employer
                        ?->name
                    ??
                    '',

                    $batch
                        ->contributionPeriod
                        ?->period_label
                    ??
                    '',

                    'Nil Contributor',

                    $status
                        ->reason
                    ??
                    'Member did not appear on the monthly contribution schedule.',
                ],
                null,
                'A'
                . $excelRow
            );


            $excelRow++;
        }


        foreach (
            range(
                'A',
                'K'
            )
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(
                    true
                );
        }


        $sheet->freezePane(
            'A2'
        );


        $filename =
            'nil_contributors_batch_'
            . $batch->id
            . '_'
            . now()->format(
                'Ymd_His'
            )
            . '.xlsx';


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
    | Reject Contribution Batch
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        ContributionImportBatch $batch
    ): RedirectResponse {
        $this->ensurePermission(
            'contributions.monthly-imports.reject'
        );


        $validated =
            $request->validate([
                'rejection_reason' => [
                    'required',
                    'string',
                    'min:5',
                    'max:3000',
                ],
            ]);


        try {

            $batch->refresh();


            /*
            |--------------------------------------------------------------------------
            | Valid Rejection Statuses
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    $batch->status,
                    [
                        'awaiting_review',
                        'validated',
                        'approved',
                    ],
                    true
                )
            ) {
                throw new RuntimeException(
                    'This contribution batch cannot be rejected at its current stage.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Already Posted
            |--------------------------------------------------------------------------
            */

            if (
                $batch->posted_at
                ||
                $batch->status === 'posted'
            ) {
                throw new RuntimeException(
                    'A posted contribution batch cannot be rejected.'
                );
            }


            $oldValues =
                $this
                    ->auditService
                    ->values(
                        $batch
                    );


            /*
            |--------------------------------------------------------------------------
            | Reject
            |--------------------------------------------------------------------------
            */

            $batch->update([
                'status' =>
                    'rejected',

                'rejected_by' =>
                    auth()->id(),

                'rejected_at' =>
                    now(),

                'rejection_reason' =>
                    $validated[
                        'rejection_reason'
                    ],

                /*
                |--------------------------------------------------------------------------
                | Any Previous Approval Is Revoked
                |--------------------------------------------------------------------------
                */

                'approved_by' =>
                    null,

                'approved_at' =>
                    null,

                'approval_notes' =>
                    null,

                'posted_by' =>
                    null,

                'posted_at' =>
                    null,

                'completed_at' =>
                    now(),
            ]);


            $batch->refresh();


            $batch->load([
                'employer',
                'uploadedBy',
            ]);


            /*
            |--------------------------------------------------------------------------
            | Notify Original Uploader
            |--------------------------------------------------------------------------
            */

            $uploader =
                $batch->uploadedBy;


            if ($uploader) {

                $rejector =
                    auth()->user();


                $rejectorName =
                    $rejector->full_name
                    ??
                    trim(
                        (
                            $rejector->first_name
                            ??
                            ''
                        )
                        . ' '
                        . (
                            $rejector->surname
                            ??
                            ''
                        )
                    );


                if (
                    $rejectorName ===
                    ''
                ) {
                    $rejectorName =
                        $rejector->username
                        ??
                        'Authorised user';
                }


                $uploader->notify(
                    new ContributionBatchRejected(
                        batch:
                            $batch,

                        reason:
                            $validated[
                                'rejection_reason'
                            ],

                        rejectedByName:
                            $rejectorName
                    )
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Audit
            |--------------------------------------------------------------------------
            */

            $this->auditService->log(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'REJECT_MONTHLY_CONTRIBUTIONS',

                description:
                    'Monthly contribution batch #'
                    . $batch->id
                    . ' was rejected.',

                auditable:
                    $batch,

                oldValues:
                    $oldValues,

                newValues:
                    $this
                        ->auditService
                        ->values(
                            $batch
                        ),

                metadata: [
                    'batch_id' =>
                        $batch->id,

                    'rejected_by' =>
                        auth()->id(),

                    'rejection_reason' =>
                        $validated[
                            'rejection_reason'
                        ],
                ],

                request:
                    $request
            );


            return redirect()
                ->route(
                    'pensions-administration.contributions.imports.show',
                    $batch
                )
                ->with(
                    'success',
                    'Contribution batch rejected successfully. The original uploader has been notified.'
                );

        } catch (Throwable $e) {

            $this->auditService->failure(
                eventType:
                    'contribution_import',

                module:
                    'Pensions Administration - Contributions',

                action:
                    'REJECT_MONTHLY_CONTRIBUTIONS',

                description:
                    'Attempt to reject contribution batch #'
                    . $batch->id
                    . ' failed.',

                failureReason:
                    $e->getMessage(),

                auditable:
                    $batch,

                request:
                    $request
            );


            return back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
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
    public function exportReinstatements(
    ContributionImportBatch $batch
): BinaryFileResponse {
    $this->ensurePermission(
        'contributions.reports.view'
    );


    $batch->load([
        'employer',
        'contributionPeriod',
    ]);


    $currentPeriod =
        $batch->contributionPeriod;


    $previousPeriod =
        \App\Models\PensionsAdministration\Contributions\ContributionPeriod::query()
            ->where(
                'employer_id',
                $batch->employer_id
            )
            ->where(
                'period_date',
                '<',
                $currentPeriod->period_date
            )
            ->orderByDesc(
                'period_date'
            )
            ->first();


    $reinstatedRows =
        collect();


    if ($previousPeriod) {

        $previousNilMemberIds =
            ContributionPeriodMemberStatus::query()
                ->where(
                    'contribution_period_id',
                    $previousPeriod->id
                )
                ->where(
                    'employer_id',
                    $batch->employer_id
                )
                ->where(
                    'contribution_status',
                    'nil_contributor'
                )
                ->pluck(
                    'member_id'
                );


        $reinstatedRows =
            ContributionImportRow::query()
                ->with([
                    'matchedMember',
                ])
                ->where(
                    'import_batch_id',
                    $batch->id
                )
                ->whereIn(
                    'matched_member_id',
                    $previousNilMemberIds
                )
                ->where(
                    'is_new_member',
                    false
                )
                ->whereIn(
                    'validation_status',
                    [
                        'valid',
                        'warning',
                    ]
                )
                ->orderBy(
                    'row_number'
                )
                ->get();
    }


    $spreadsheet =
        new Spreadsheet();


    $sheet =
        $spreadsheet
            ->getActiveSheet();


    $sheet->setTitle(
        'Reinstatements'
    );


    $headers = [
        'Excel Row',
        'PENERP Member Number',
        'PenAd Member Number',
        'Fundworx Member Number',
        'Staff Number',
        'National ID',
        'Surname',
        'First Names',
        'Employer',
        'Previous Period',
        'Current Period',
        'Previous Status',
        'Current Status',
    ];


    $sheet->fromArray(
        $headers,
        null,
        'A1'
    );


    $sheet
        ->getStyle('A1:M1')
        ->getFont()
        ->setBold(true);


    $excelRow =
        2;


    foreach (
        $reinstatedRows
        as $row
    ) {
        $data =
            $row->normalized_data
            ?? [];


        $member =
            $row->matchedMember;


        $sheet->fromArray(
            [
                $row->row_number,

                $member?->member_number
                    ??
                    $data['penerp_member_number']
                    ??
                    '',

                $member?->penad_member_number
                    ??
                    $data['penad_member_number']
                    ??
                    '',

                $member?->fundworx_member_number
                    ??
                    $data['fundworx_member_number']
                    ??
                    '',

                $data['staff_number']
                    ?? '',

                $member?->national_id
                    ??
                    $data['national_id']
                    ??
                    '',

                $member?->surname
                    ??
                    $data['surname']
                    ??
                    '',

                $member?->first_names
                    ??
                    $data['first_names']
                    ??
                    '',

                $batch->employer?->name
                    ?? '',

                $previousPeriod?->period_label
                    ?? '',

                $currentPeriod?->period_label
                    ?? '',

                'Nil Contributor',

                'Contributing',
            ],
            null,
            'A'
            . $excelRow
        );


        $excelRow++;
    }


    foreach (
        range('A', 'M')
        as $column
    ) {
        $sheet
            ->getColumnDimension(
                $column
            )
            ->setAutoSize(true);
    }


    $filename =
        'reinstated_contributors_batch_'
        . $batch->id
        . '_'
        . now()->format('Ymd_His')
        . '.xlsx';


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


    return response()
        ->download(
            $path,
            $filename
        )
        ->deleteFileAfterSend(true);
}
}