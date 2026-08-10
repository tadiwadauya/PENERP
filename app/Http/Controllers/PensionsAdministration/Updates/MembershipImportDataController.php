<?php

namespace App\Http\Controllers\PensionsAdministration\Updates;

use App\Http\Controllers\Controller;
use App\Models\PensionsAdministration\Updates\MembershipImportBatch;
use App\Models\PensionsAdministration\Updates\MembershipImportRow;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipImportDataController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | All Validated Rows - DataTables
    |--------------------------------------------------------------------------
    */

    public function data(
        Request $request,
        MembershipImportBatch $batch
    ): JsonResponse {
        $query = MembershipImportRow::query()
            ->with([
                'matchedEmployer',
                'matchedMember',
            ])
            ->where('import_batch_id', $batch->id);

        return $this->dataTableResponse(
            $request,
            $query,
            $batch
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Exceptions Only - DataTables
    |--------------------------------------------------------------------------
    */

    public function exceptions(
        Request $request,
        MembershipImportBatch $batch
    ): JsonResponse {
        $query = MembershipImportRow::query()
            ->with([
                'matchedEmployer',
                'matchedMember',
            ])
            ->where('import_batch_id', $batch->id)
            ->where(function (Builder $query) {
                $query
                    ->where('validation_status', 'error')
                    ->orWhere('validation_status', 'warning')
                    ->orWhereIn('duplicate_status', [
                        'exact',
                        'probable',
                        'possible',
                    ]);
            });

        return $this->dataTableResponse(
            $request,
            $query,
            $batch
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DataTables Response
    |--------------------------------------------------------------------------
    */

    private function dataTableResponse(
        Request $request,
        Builder $query,
        MembershipImportBatch $batch
    ): JsonResponse {
        $draw = (int) $request->input('draw', 1);

        $start = max(
            0,
            (int) $request->input('start', 0)
        );

        $length = (int) $request->input(
            'length',
            25
        );

        if ($length < 1 || $length > 500) {
            $length = 25;
        }

        /*
        |--------------------------------------------------------------------------
        | Total Before Search
        |--------------------------------------------------------------------------
        */

        $recordsTotal = (clone $query)->count();

        /*
        |--------------------------------------------------------------------------
        | Global Search
        |--------------------------------------------------------------------------
        */

        $search = trim(
            (string) $request->input(
                'search.value',
                ''
            )
        );

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function (Builder $query) use ($like) {
                $query
                    ->where('normalized_data', 'like', $like)
                    ->orWhere('raw_data', 'like', $like)
                    ->orWhere('error_messages', 'like', $like)
                    ->orWhere('warning_messages', 'like', $like)
                    ->orWhere('duplicate_reasons', 'like', $like)
                    ->orWhere('validation_status', 'like', $like)
                    ->orWhere('duplicate_status', 'like', $like)
                    ->orWhere('review_decision', 'like', $like)
                    ->orWhereRaw(
                        'CAST(row_number AS NVARCHAR(50)) LIKE ?',
                        [$like]
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Optional Filters
        |--------------------------------------------------------------------------
        */

        if ($request->filled('validation_status')) {
            $query->where(
                'validation_status',
                $request->input(
                    'validation_status'
                )
            );
        }

        if ($request->filled('duplicate_status')) {
            $query->where(
                'duplicate_status',
                $request->input(
                    'duplicate_status'
                )
            );
        }

        if ($request->filled('review_decision')) {
            $query->where(
                'review_decision',
                $request->input(
                    'review_decision'
                )
            );
        }

        $recordsFiltered = (clone $query)->count();

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $orderColumn = (int) $request->input(
            'order.0.column',
            0
        );

        $orderDirection = strtolower(
            (string) $request->input(
                'order.0.dir',
                'asc'
            )
        );

        if (!in_array(
            $orderDirection,
            ['asc', 'desc'],
            true
        )) {
            $orderDirection = 'asc';
        }

        $columns = [
            0 => 'row_number',
            1 => 'row_number',
            2 => 'row_number',
            3 => 'row_number',
            4 => 'row_number',
            5 => 'validation_status',
            6 => 'duplicate_status',
            7 => 'review_decision',
        ];

        $sortColumn = $columns[$orderColumn]
            ?? 'row_number';

        $query->orderBy(
            $sortColumn,
            $orderDirection
        );

        /*
        |--------------------------------------------------------------------------
        | Page
        |--------------------------------------------------------------------------
        */

        $rows = $query
            ->skip($start)
            ->take($length)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Format Results
        |--------------------------------------------------------------------------
        */

        $data = [];

        foreach ($rows as $row) {
            $normalized = $row->normalized_data ?? [];

            $data[] = [
                'row_number' =>
                    $row->row_number,

                'member' =>
                    $this->memberHtml(
                        $normalized
                    ),

                'national_id' =>
                    e(
                        $normalized['national_id']
                        ?? '-'
                    ),

                'references' =>
                    $this->referencesHtml(
                        $normalized
                    ),

                'employer' =>
                    $this->employerHtml(
                        $row,
                        $normalized
                    ),

                'validation' =>
                    $this->validationHtml(
                        $row
                    ),

                'duplicate' =>
                    $this->duplicateHtml(
                        $row
                    ),

                'decision' =>
                    $this->decisionHtml(
                        $row
                    ),

                'exception_fields' =>
                    $this->exceptionFieldsHtml(
                        $row
                    ),

                'details' =>
                    $this->detailsHtml(
                        $row
                    ),

                'actions' =>
                    $this->actionsHtml(
                        $batch,
                        $row
                    ),
            ];
        }

        return response()->json([
            'draw' => $draw,

            'recordsTotal' =>
                $recordsTotal,

            'recordsFiltered' =>
                $recordsFiltered,

            'data' =>
                $data,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Download Full Exception Report
    |--------------------------------------------------------------------------
    */

    public function downloadExceptions(
        MembershipImportBatch $batch
    ): StreamedResponse {
        $rows = MembershipImportRow::query()
            ->with([
                'matchedEmployer',
                'matchedMember',
            ])
            ->where(
                'import_batch_id',
                $batch->id
            )
            ->where(function (Builder $query) {
                $query
                    ->where(
                        'validation_status',
                        'error'
                    )
                    ->orWhere(
                        'validation_status',
                        'warning'
                    )
                    ->orWhereIn(
                        'duplicate_status',
                        [
                            'exact',
                            'probable',
                            'possible',
                        ]
                    );
            })
            ->orderBy('row_number')
            ->get();

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet
            ->getActiveSheet();

        $sheet->setTitle(
            'Member Exceptions'
        );

        /*
        |--------------------------------------------------------------------------
        | Headers
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Excel Row',
            'Validation Status',
            'Duplicate Status',
            'Fields Requiring Attention',

            'Surname',
            'First Names',
            'Other Names',
            'Maiden Name',

            'National ID',
            'Date of Birth',
            'Membership Status',

            'PenAd Member Number',
            'Fundworx Member Number',

            'PENERP Employer Number',
            'PenAd Employer Number',
            'Fundworx Employer Number',

            'Staff Number',
            'Vote Number',

            'Matched Employer',

            'Errors',
            'Warnings',
            'Duplicate Reasons',

            'Review Decision',
        ];

        $sheet->fromArray(
            $headers,
            null,
            'A1'
        );

        /*
        |--------------------------------------------------------------------------
        | Heading Style
        |--------------------------------------------------------------------------
        */

        $sheet->getStyle(
            'A1:W1'
        )->getFont()->setBold(true);

        $sheet->getStyle(
            'A1:W1'
        )->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setRGB('1F4E78');

        $sheet->getStyle(
            'A1:W1'
        )->getFont()
            ->getColor()
            ->setRGB('FFFFFF');

        /*
        |--------------------------------------------------------------------------
        | Rows
        |--------------------------------------------------------------------------
        */

        $excelRow = 2;

        foreach ($rows as $row) {
            $data = $row->normalized_data ?? [];

            $errorFields =
                implode(
                    ', ',
                    $this->exceptionFields(
                        $row
                    )
                );

            $errors = implode(
                ' | ',
                $row->error_messages
                ?? []
            );

            $warnings = implode(
                ' | ',
                $row->warning_messages
                ?? []
            );

            $duplicates = implode(
                ' | ',
                $row->duplicate_reasons
                ?? []
            );

            $sheet->fromArray([
                $row->row_number,

                strtoupper(
                    $row->validation_status
                ),

                strtoupper(
                    $row->duplicate_status
                ),

                $errorFields,

                $data['surname']
                    ?? null,

                $data['first_names']
                    ?? null,

                $data['other_names']
                    ?? null,

                $data['maiden_name']
                    ?? null,

                $data['national_id']
                    ?? null,

                $data['date_of_birth']
                    ?? null,

                $data['membership_status']
                    ?? null,

                $data['penad_member_number']
                    ?? null,

                $data['fundworx_member_number']
                    ?? null,

                $data['penerp_employer_number']
                    ?? null,

                $data['penad_employer_number']
                    ?? null,

                $data['fundworx_employer_number']
                    ?? null,

                $data['staff_number']
                    ?? null,

                $data['vote_number']
                    ?? null,

                $row->matchedEmployer?->name,

                $errors,
                $warnings,
                $duplicates,

                $row->review_decision,
            ], null, 'A' . $excelRow);

            /*
            |--------------------------------------------------------------------------
            | Exception Row Highlighting
            |--------------------------------------------------------------------------
            */

            if (
                $row->validation_status
                === 'error'
            ) {
                $sheet->getStyle(
                    'A'
                    . $excelRow
                    . ':W'
                    . $excelRow
                )
                    ->getFill()
                    ->setFillType(
                        Fill::FILL_SOLID
                    )
                    ->getStartColor()
                    ->setRGB(
                        'FCE8E6'
                    );

            } elseif (
                $row->validation_status
                === 'warning'
                ||
                $row->duplicate_status
                !== 'none'
            ) {
                $sheet->getStyle(
                    'A'
                    . $excelRow
                    . ':W'
                    . $excelRow
                )
                    ->getFill()
                    ->setFillType(
                        Fill::FILL_SOLID
                    )
                    ->getStartColor()
                    ->setRGB(
                        'FFF4CE'
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Attention Column Strong Highlight
            |--------------------------------------------------------------------------
            */

            $sheet->getStyle(
                'D' . $excelRow
            )
                ->getFont()
                ->setBold(true);

            $excelRow++;
        }

        /*
        |--------------------------------------------------------------------------
        | Formatting
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane(
            'A2'
        );

        $sheet->setAutoFilter(
            'A1:W' . max(
                1,
                $excelRow - 1
            )
        );

        $sheet->getStyle(
            'A1:W'
            . max(
                1,
                $excelRow - 1
            )
        )
            ->getAlignment()
            ->setVertical(
                Alignment::VERTICAL_TOP
            );

        $sheet->getStyle(
            'D2:W'
            . max(
                2,
                $excelRow - 1
            )
        )
            ->getAlignment()
            ->setWrapText(true);

        /*
        |--------------------------------------------------------------------------
        | Column Widths
        |--------------------------------------------------------------------------
        */

        $widths = [
            'A' => 12,
            'B' => 16,
            'C' => 16,
            'D' => 35,

            'E' => 20,
            'F' => 24,
            'G' => 24,
            'H' => 20,

            'I' => 22,
            'J' => 16,
            'K' => 18,

            'L' => 20,
            'M' => 22,

            'N' => 22,
            'O' => 22,
            'P' => 24,

            'Q' => 18,
            'R' => 18,

            'S' => 30,

            'T' => 55,
            'U' => 55,
            'V' => 55,

            'W' => 20,
        ];

        foreach (
            $widths
            as $column => $width
        ) {
            $sheet->getColumnDimension(
                $column
            )->setWidth(
                $width
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stream File
        |--------------------------------------------------------------------------
        */

        $filename =
            'PENERP_Member_Exception_Report_Batch_'
            . $batch->id
            . '_'
            . now()->format(
                'Ymd_His'
            )
            . '.xlsx';

        return response()->streamDownload(
            function () use ($spreadsheet) {
                $writer = new Xlsx(
                    $spreadsheet
                );

                $writer->save(
                    'php://output'
                );

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Member HTML
    |--------------------------------------------------------------------------
    */

    private function memberHtml(
        array $data
    ): string {
        $name =
            e(
                $data['surname']
                ?? '-'
            )
            . ', '
            . e(
                $data['first_names']
                ?? '-'
            );

        $html =
            '<strong>'
            . $name
            . '</strong>';

        if (
            !empty(
                $data['other_names']
            )
        ) {
            $html .=
                '<br><small>Other: '
                . e(
                    $data['other_names']
                )
                . '</small>';
        }

        if (
            !empty(
                $data['maiden_name']
            )
        ) {
            $html .=
                '<br><small class="text-muted">Maiden: '
                . e(
                    $data['maiden_name']
                )
                . '</small>';
        }

        return $html;
    }

    private function referencesHtml(
        array $data
    ): string {
        return
            '<small><strong>PenAd:</strong> '
            . e(
                $data['penad_member_number']
                ?? '-'
            )
            . '</small>'
            . '<br>'
            . '<small><strong>Fundworx:</strong> '
            . e(
                $data['fundworx_member_number']
                ?? '-'
            )
            . '</small>';
    }

    private function employerHtml(
        MembershipImportRow $row,
        array $data
    ): string {
        if ($row->matchedEmployer) {
            return
                '<strong>'
                . e(
                    $row
                        ->matchedEmployer
                        ->name
                )
                . '</strong>'
                . '<br>'
                . '<small>'
                . e(
                    $row
                        ->matchedEmployer
                        ->employer_number
                )
                . '</small>';
        }

        return
            '<span class="text-danger">'
            . e(
                $data[
                    'penerp_employer_number'
                ]
                ?? $data[
                    'penad_employer_number'
                ]
                ?? 'Not Matched'
            )
            . '</span>';
    }

    private function validationHtml(
        MembershipImportRow $row
    ): string {
        return match (
            $row->validation_status
        ) {
            'valid' =>
                '<span class="badge bg-success">Valid</span>',

            'warning' =>
                '<span class="badge bg-warning text-dark">Warning</span>',

            default =>
                '<span class="badge bg-danger">Error</span>',
        };
    }

    private function duplicateHtml(
        MembershipImportRow $row
    ): string {
        return match (
            $row->duplicate_status
        ) {
            'exact' =>
                '<span class="badge bg-danger">Exact Match</span>',

            'probable' =>
                '<span class="badge bg-warning text-dark">Probable</span>',

            'possible' =>
                '<span class="badge bg-info">Possible</span>',

            default =>
                '<span class="text-muted">None</span>',
        };
    }

    private function decisionHtml(
        MembershipImportRow $row
    ): string {
        if (
            $row->review_decision
            === 'pending'
        ) {
            return
                '<span class="badge bg-secondary">Pending</span>';
        }

        if (
            $row->review_decision
            === 'reject'
        ) {
            return
                '<span class="badge bg-danger">Removed</span>';
        }

        return
            '<span class="badge bg-success">'
            . e(
                ucwords(
                    str_replace(
                        '_',
                        ' ',
                        $row
                            ->review_decision
                    )
                )
            )
            . '</span>';
    }

    /*
    |--------------------------------------------------------------------------
    | Exception Fields
    |--------------------------------------------------------------------------
    */

    private function exceptionFields(
        MembershipImportRow $row
    ): array {
        $messages = array_merge(
            $row->error_messages
                ?? [],

            $row->warning_messages
                ?? [],

            $row->duplicate_reasons
                ?? []
        );

        $fields = [];

        foreach ($messages as $message) {
            $message = strtolower(
                $message
            );

            if (
                str_contains(
                    $message,
                    'national id'
                )
            ) {
                $fields[] =
                    'National ID';
            }

            if (
                str_contains(
                    $message,
                    'employer'
                )
            ) {
                $fields[] =
                    'Employer';
            }

            if (
                str_contains(
                    $message,
                    'staff number'
                )
            ) {
                $fields[] =
                    'Staff Number';
            }

            if (
                str_contains(
                    $message,
                    'vote number'
                )
            ) {
                $fields[] =
                    'Vote Number';
            }

            if (
                str_contains(
                    $message,
                    'surname'
                )
            ) {
                $fields[] =
                    'Surname';
            }

            if (
                str_contains(
                    $message,
                    'first name'
                )
            ) {
                $fields[] =
                    'First Names';
            }

            if (
                str_contains(
                    $message,
                    'other name'
                )
            ) {
                $fields[] =
                    'Other Names';
            }

            if (
                str_contains(
                    $message,
                    'maiden'
                )
            ) {
                $fields[] =
                    'Maiden Name';
            }

            if (
                str_contains(
                    $message,
                    'date of birth'
                )
            ) {
                $fields[] =
                    'Date of Birth';
            }

            if (
                str_contains(
                    $message,
                    'date joined fund'
                )
            ) {
                $fields[] =
                    'Date Joined Fund';
            }

            if (
                str_contains(
                    $message,
                    'date joined employer'
                )
            ) {
                $fields[] =
                    'Date Joined Employer';
            }

            if (
                str_contains(
                    $message,
                    'membership status'
                )
            ) {
                $fields[] =
                    'Membership Status';
            }

            if (
                str_contains(
                    $message,
                    'email'
                )
            ) {
                $fields[] =
                    'Email';
            }

            if (
                str_contains(
                    $message,
                    'penad member'
                )
            ) {
                $fields[] =
                    'PenAd Member Number';
            }

            if (
                str_contains(
                    $message,
                    'fundworx member'
                )
            ) {
                $fields[] =
                    'Fundworx Member Number';
            }

            if (
                str_contains(
                    $message,
                    'penerp member'
                )
            ) {
                $fields[] =
                    'PENERP Member Number';
            }
        }

        if (
            $row->duplicate_status
            !== 'none'
        ) {
            $fields[] =
                'Duplicate Review';
        }

        return array_values(
            array_unique(
                $fields
            )
        );
    }

    private function exceptionFieldsHtml(
        MembershipImportRow $row
    ): string {
        $fields =
            $this->exceptionFields(
                $row
            );

        if (!$fields) {
            return
                '<span class="text-success">'
                . '<i class="mdi mdi-check-circle-outline me-1"></i>'
                . 'None'
                . '</span>';
        }

        $html = '';

        foreach ($fields as $field) {
            $html .=
                '<span class="badge bg-danger-subtle text-danger me-1 mb-1">'
                . e($field)
                . '</span>';
        }

        return $html;
    }

    private function detailsHtml(
        MembershipImportRow $row
    ): string {
        $html = '';

        foreach (
            $row->error_messages
            ?? []
            as $message
        ) {
            $html .=
                '<div class="exception-message exception-error">'
                . '<i class="mdi mdi-alert-circle-outline me-1"></i>'
                . e($message)
                . '</div>';
        }

        foreach (
            $row->warning_messages
            ?? []
            as $message
        ) {
            $html .=
                '<div class="exception-message exception-warning">'
                . '<i class="mdi mdi-alert-outline me-1"></i>'
                . e($message)
                . '</div>';
        }

        foreach (
            $row->duplicate_reasons
            ?? []
            as $message
        ) {
            $html .=
                '<div class="exception-message exception-duplicate">'
                . '<i class="mdi mdi-content-copy me-1"></i>'
                . e($message)
                . '</div>';
        }

        if ($html === '') {
            $html =
                '<span class="text-success">'
                . '<i class="mdi mdi-check-circle-outline me-1"></i>'
                . 'Ready for import'
                . '</span>';
        }

        return $html;
    }

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    private function actionsHtml(
        MembershipImportBatch $batch,
        MembershipImportRow $row
    ): string {
        if (
            $row->review_decision
            === 'reject'
        ) {
            return
                '<span class="text-muted">'
                . 'Removed from import'
                . '</span>';
        }

        $editUrl = route(
            'pensions-administration.updates.imports.rows.edit',
            [
                $batch,
                $row,
            ]
        );

        $removeUrl = route(
            'pensions-administration.updates.imports.rows.remove',
            [
                $batch,
                $row,
            ]
        );

        $html =
            '<div class="d-flex gap-1 flex-wrap">';

        $html .=
            '<a href="'
            . e($editUrl)
            . '" class="btn btn-sm btn-primary">'
            . '<i class="mdi mdi-pencil-outline me-1"></i>'
            . 'Correct'
            . '</a>';

        $html .=
            '<form method="POST" action="'
            . e($removeUrl)
            . '" onsubmit="return confirm(\'Remove Excel row '
            . (int) $row->row_number
            . ' from this import?\');">'

            . '<input type="hidden" name="_token" value="'
            . csrf_token()
            . '">'

            . '<input type="hidden" name="_method" value="DELETE">'

            . '<button type="submit" class="btn btn-sm btn-outline-danger">'
            . '<i class="mdi mdi-delete-outline me-1"></i>'
            . 'Delete Row'
            . '</button>'

            . '</form>';

        $html .= '</div>';

        return $html;
    }
}