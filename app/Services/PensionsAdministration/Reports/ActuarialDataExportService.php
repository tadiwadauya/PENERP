<?php

namespace App\Services\PensionsAdministration\Reports;

use App\Models\PensionsAdministration\Reports\ActuarialDataExtractBatch;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ActuarialDataExportService
{
    private array $months = [];

    /*
    |--------------------------------------------------------------------------
    | Generate Extract
    |--------------------------------------------------------------------------
    */

    public function generate(
        ActuarialDataExtractBatch $batch
    ): void {
        $from =
            Carbon::parse(
                $batch->date_from
            )->startOfMonth();

        $to =
            Carbon::parse(
                $batch->date_to
            )->endOfMonth();

        $this->months =
            $this->buildMonths(
                $from,
                $to
            );

        /*
        |--------------------------------------------------------------------------
        | Workbook
        |--------------------------------------------------------------------------
        */

        $spreadsheet =
            new Spreadsheet();

        $activeSheet =
            $spreadsheet->getActiveSheet();

        $activeSheet->setTitle(
            'Active Members'
        );

        $contributingSheet =
            $spreadsheet->createSheet();

        $contributingSheet->setTitle(
            'Active and Contributing'
        );

        $nilSheet =
            $spreadsheet->createSheet();

        $nilSheet->setTitle(
            'Nil Contributors'
        );

        $exitedSheet =
            $spreadsheet->createSheet();

        $exitedSheet->setTitle(
            'Exited Members'
        );

        /*
        |--------------------------------------------------------------------------
        | Headers
        |--------------------------------------------------------------------------
        */

        $headers =
            $this->headers();

        $this->writeHeaders(
            $activeSheet,
            $headers
        );

        $this->writeHeaders(
            $contributingSheet,
            $headers
        );

        $this->writeHeaders(
            $nilSheet,
            $headers
        );

        $this->writeHeaders(
            $exitedSheet,
            $headers
        );

        $batch->update([
            'progress_percentage' => 8,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Contributions In Selected Period
        |--------------------------------------------------------------------------
        */

        $contributions =
            $this->loadContributions(
                batch: $batch,
                from: $from,
                to: $to
            );

        $batch->update([
            'progress_percentage' => 25,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Active Members
        |--------------------------------------------------------------------------
        |
        | Active Members:
        |     Every member currently marked Active.
        |
        | Active and Contributing:
        |     Active member with a positive contribution / AVC somewhere
        |     inside the selected actuarial period.
        |
        | Nil Contributors:
        |     Active member without a positive contribution / AVC anywhere
        |     inside the selected actuarial period.
        |
        */

        $activeMembers =
            $this->memberQuery(
                batch: $batch,
                status: 'active'
            )->get();

        $activeRow = 2;
        $contributingRow = 2;
        $nilRow = 2;

        $activeCount = 0;
        $contributingCount = 0;
        $nilCount = 0;

        foreach ($activeMembers as $member) {
            $memberContributions =
                $contributions[
                    (int) $member->id
                ]
                ??
                [];

            /*
            |--------------------------------------------------------------------------
            | All Active Members
            |--------------------------------------------------------------------------
            */

            $this->writeMemberRow(
                sheet: $activeSheet,
                rowNumber: $activeRow++,
                member: $member,
                contributions: $memberContributions,
                isExited: false
            );

            $activeCount++;

            /*
            |--------------------------------------------------------------------------
            | Active And Contributing / Nil Contributors
            |--------------------------------------------------------------------------
            */

            if (
                $this->hasContributionInSelectedPeriod(
                    member: $member,
                    contributions: $memberContributions
                )
            ) {
                $this->writeMemberRow(
                    sheet: $contributingSheet,
                    rowNumber: $contributingRow++,
                    member: $member,
                    contributions: $memberContributions,
                    isExited: false
                );

                $contributingCount++;
            } else {
                $this->writeMemberRow(
                    sheet: $nilSheet,
                    rowNumber: $nilRow++,
                    member: $member,
                    contributions: $memberContributions,
                    isExited: false
                );

                $nilCount++;
            }
        }

        unset(
            $activeMembers
        );

        /*
        |--------------------------------------------------------------------------
        | Active Counts
        |--------------------------------------------------------------------------
        |
        | active_contributing_members is only written if that column has already
        | been added to the actuarial batch table.
        |
        */

        $activeBatchUpdates = [
            'active_members' =>
                $activeCount,

            'nil_contributors' =>
                $nilCount,

            'progress_percentage' =>
                60,
        ];

        if (
            $this->batchColumnExists(
                'active_contributing_members'
            )
        ) {
            $activeBatchUpdates[
                'active_contributing_members'
            ] =
                $contributingCount;
        }

        $batch->update(
            $activeBatchUpdates
        );

        /*
        |--------------------------------------------------------------------------
        | Exited Members
        |--------------------------------------------------------------------------
        |
        | A member qualifies as an exit for this extract only when:
        |
        | 1. Membership status is Exited;
        | 2. A genuine exit_date exists; and
        | 3. exit_date falls inside the selected period.
        |
        | We NEVER infer an exit date from the final contribution month.
        |
        */

        $exitedMembers =
            $this->memberQuery(
                batch: $batch,
                status: 'exited',
                from: $from,
                to: $to
            )->get();

        $exitedCount =
            $exitedMembers->count();

        $exitedRow = 2;

        foreach ($exitedMembers as $member) {
            $memberContributions =
                $contributions[
                    (int) $member->id
                ]
                ??
                [];

            $this->writeMemberRow(
                sheet: $exitedSheet,
                rowNumber: $exitedRow++,
                member: $member,
                contributions: $memberContributions,
                isExited: true
            );
        }

        unset(
            $exitedMembers,
            $contributions
        );

        $batch->update([
            'exited_members' =>
                $exitedCount,

            'progress_percentage' =>
                82,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Sheet Formatting
        |--------------------------------------------------------------------------
        */

        foreach (
            $spreadsheet->getWorksheetIterator()
            as $sheet
        ) {
            $sheet->freezePane(
                'A2'
            );

            $sheet->setAutoFilter(
                $sheet->calculateWorksheetDimension()
            );

            $sheet
                ->getRowDimension(1)
                ->setRowHeight(30);

            /*
            |--------------------------------------------------------------------------
            | Identity / Member Columns
            |--------------------------------------------------------------------------
            */

            foreach (
                range(
                    1,
                    min(
                        21,
                        count($headers)
                    )
                )
                as $columnIndex
            ) {
                $column =
                    Coordinate::stringFromColumnIndex(
                        $columnIndex
                    );

                $sheet
                    ->getColumnDimension(
                        $column
                    )
                    ->setAutoSize(
                        true
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Financial Columns
            |--------------------------------------------------------------------------
            */

            for (
                $columnIndex = 22;
                $columnIndex <= count($headers);
                $columnIndex++
            ) {
                $column =
                    Coordinate::stringFromColumnIndex(
                        $columnIndex
                    );

                $sheet
                    ->getColumnDimension(
                        $column
                    )
                    ->setWidth(
                        16
                    );
            }
        }

        $batch->update([
            'progress_percentage' =>
                90,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Save
        |--------------------------------------------------------------------------
        */

        $directory =
            'actuarial-data-extracts/'
            . $batch->id;

        Storage::disk(
            'local'
        )->makeDirectory(
            $directory
        );

        $fileName =
            'PENERP_Actuarial_Data_'
            . $from->format('Ymd')
            . '_to_'
            . $to->format('Ymd')
            . '.xlsx';

        $filePath =
            $directory
            . '/'
            . $fileName;

        $absolutePath =
            Storage::disk(
                'local'
            )->path(
                $filePath
            );

        $writer =
            new Xlsx(
                $spreadsheet
            );

        $writer->setPreCalculateFormulas(
            false
        );

        $writer->save(
            $absolutePath
        );

        $spreadsheet
            ->disconnectWorksheets();

        unset(
            $writer,
            $spreadsheet
        );

        gc_collect_cycles();

        $batch->update([
            'file_path' =>
                $filePath,

            'file_name' =>
                $fileName,

            'progress_percentage' =>
                98,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Member Query
    |--------------------------------------------------------------------------
    */

    private function memberQuery(
        ActuarialDataExtractBatch $batch,
        string $status,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Builder {
        $query =
            DB::table(
                'members AS m'
            )
                ->join(
                    'member_employments AS me',
                    'me.member_id',
                    '=',
                    'm.id'
                )
                ->join(
                    'employers AS e',
                    'e.id',
                    '=',
                    'me.employer_id'
                )
                ->whereNull(
                    'm.deleted_at'
                )
                ->whereNull(
                    'e.deleted_at'
                )
                ->whereRaw(
                    'LOWER(LTRIM(RTRIM(m.membership_status))) = ?',
                    [
                        strtolower(
                            $status
                        ),
                    ]
                );

        /*
        |--------------------------------------------------------------------------
        | Employer Filter
        |--------------------------------------------------------------------------
        */

        if ($batch->employer_id) {
            $query->where(
                'e.id',
                $batch->employer_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Exit Period Filter
        |--------------------------------------------------------------------------
        */

        if (
            strtolower($status)
            ===
            'exited'
            &&
            $from
            &&
            $to
        ) {
            $query
                ->whereNotNull(
                    'm.exit_date'
                )
                ->whereBetween(
                    'm.exit_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                );
        }

        return $query
            ->selectRaw("
                m.id,
                m.member_number,
                m.penad_member_number,
                m.fundworx_member_number,
                m.surname,
                m.first_names,
                m.other_names,
                m.national_id,
                m.date_of_birth,
                m.date_joined_fund,
                m.gender,
                m.membership_status,
                m.exit_date,
                m.exit_reason,

                e.id AS employer_id,
                e.employer_number,
                e.penad_employer_number,
                e.fundworx_employer_number,
                e.name AS employer_name,

                MAX(me.staff_number) AS staff_number,
                MAX(me.vote_number) AS vote_number,
                MAX(me.date_joined_employer) AS date_joined_employer
            ")
            ->groupBy([
                'm.id',
                'm.member_number',
                'm.penad_member_number',
                'm.fundworx_member_number',
                'm.surname',
                'm.first_names',
                'm.other_names',
                'm.national_id',
                'm.date_of_birth',
                'm.date_joined_fund',
                'm.gender',
                'm.membership_status',
                'm.exit_date',
                'm.exit_reason',

                'e.id',
                'e.employer_number',
                'e.penad_employer_number',
                'e.fundworx_employer_number',
                'e.name',
            ])
            ->orderBy(
                'e.name'
            )
            ->orderBy(
                'm.surname'
            )
            ->orderBy(
                'm.first_names'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Load Contributions
    |--------------------------------------------------------------------------
    */

    private function loadContributions(
        ActuarialDataExtractBatch $batch,
        Carbon $from,
        Carbon $to
    ): array {
        $query =
            DB::table(
                'member_contributions AS mc'
            )
                ->whereBetween(
                    'mc.period_date',
                    [
                        $from->toDateString(),
                        $to->toDateString(),
                    ]
                );

        if ($batch->employer_id) {
            $query->where(
                'mc.employer_id',
                $batch->employer_id
            );
        }

        $rows =
            $query
                ->selectRaw("
                    mc.member_id,
                    mc.employer_id,
                    mc.period_year,
                    mc.period_month,

                    SUM(
                        CASE
                            WHEN mc.source_system = 'historical_migration'
                            THEN COALESCE(mc.basic_pay, 0)
                            ELSE
                                CASE
                                    WHEN COALESCE(mc.zwg_basic_pay, 0) <> 0
                                    THEN COALESCE(mc.zwg_basic_pay, 0)
                                    ELSE COALESCE(mc.basic_pay, 0)
                                END
                        END
                    ) AS basic_pay,

                    SUM(
                        CASE
                            WHEN mc.source_system = 'historical_migration'
                            THEN COALESCE(mc.employee_contribution, 0)
                            ELSE
                                CASE
                                    WHEN COALESCE(mc.zwg_employee_contribution, 0) <> 0
                                    THEN COALESCE(mc.zwg_employee_contribution, 0)
                                    ELSE COALESCE(mc.employee_contribution, 0)
                                END
                        END
                    ) AS employee_contribution,

                    SUM(
                        CASE
                            WHEN mc.source_system = 'historical_migration'
                            THEN COALESCE(mc.employee_avc, 0)
                            ELSE
                                CASE
                                    WHEN COALESCE(mc.zwg_employee_avc, 0) <> 0
                                    THEN COALESCE(mc.zwg_employee_avc, 0)
                                    ELSE COALESCE(mc.employee_avc, 0)
                                END
                        END
                    ) AS employee_avc,

                    SUM(
                        CASE
                            WHEN mc.source_system = 'historical_migration'
                            THEN COALESCE(mc.employer_contribution, 0)
                            ELSE
                                CASE
                                    WHEN COALESCE(mc.zwg_employer_contribution, 0) <> 0
                                    THEN COALESCE(mc.zwg_employer_contribution, 0)
                                    ELSE COALESCE(mc.employer_contribution, 0)
                                END
                        END
                    ) AS employer_contribution,

                    SUM(
                        CASE
                            WHEN mc.source_system = 'historical_migration'
                            THEN COALESCE(mc.employer_avc, 0)
                            ELSE
                                CASE
                                    WHEN COALESCE(mc.zwg_employer_avc, 0) <> 0
                                    THEN COALESCE(mc.zwg_employer_avc, 0)
                                    ELSE COALESCE(mc.employer_avc, 0)
                                END
                        END
                    ) AS employer_avc
                ")
                ->groupBy([
                    'mc.member_id',
                    'mc.employer_id',
                    'mc.period_year',
                    'mc.period_month',
                ])
                ->get();

        $result = [];

        foreach ($rows as $row) {
            $memberId =
                (int) $row->member_id;

            $employerId =
                (int) $row->employer_id;

            $key =
                sprintf(
                    '%04d-%02d',
                    (int) $row->period_year,
                    (int) $row->period_month
                );

            $result[
                $memberId
            ][
                $employerId
            ][
                $key
            ] = [
                'basic_pay' =>
                    (float) $row->basic_pay,

                'employee_contribution' =>
                    (float) $row->employee_contribution,

                'employee_avc' =>
                    (float) $row->employee_avc,

                'employer_contribution' =>
                    (float) $row->employer_contribution,

                'employer_avc' =>
                    (float) $row->employer_avc,
            ];
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | Headers
    |--------------------------------------------------------------------------
    */

    private function headers(): array
    {
        $headers = [
            'Employer Number',
            'PenAd Employer Number',
            'Employer Name',
            'Member ID',
            'PENERP Member No',
            'PenAd Member No',
            'Fundworx Member No',
            'Staff Number',
            'Surname',
            'First Names',
            'National ID',
            'Date Of Birth',
            'Date Joined Fund',
            'Date Joined Employer',
            'Date Of Exit',
            'Type Of Exit',
            'Pensionable Service To Date',
            'Gender',
            'Member Status',
            'Current Monthly Pensionable Salary',
            'Current Annual Pensionable Salary',
        ];

        foreach (
            $this->months
            as $month
        ) {
            $label =
                $month[
                    'date'
                ]->format(
                    'M Y'
                );

            $headers[] =
                $label
                . ' Monthly Salary';

            $headers[] =
                $label
                . ' EE Cont';

            $headers[] =
                $label
                . ' EE AV Cont';

            $headers[] =
                $label
                . ' ER Cont';

            $headers[] =
                $label
                . ' ER AV Cont';
        }

        return array_merge(
            $headers,
            [
                'Total EE Cont',
                'Total EE AV Cont',
                'Total ER Cont',
                'Total ER AV Cont',
                'Exit Benefit Paid',
                'Date Of Payment',
                'Benefit Outstanding',
                'Update Date',
                'Transaction Date',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Write Header
    |--------------------------------------------------------------------------
    */

    private function writeHeaders(
        mixed $sheet,
        array $headers
    ): void {
        foreach (
            $headers
            as $index => $header
        ) {
            $column =
                Coordinate::stringFromColumnIndex(
                    $index + 1
                );

            $sheet->setCellValue(
                $column . '1',
                $header
            );
        }

        $lastColumn =
            Coordinate::stringFromColumnIndex(
                count($headers)
            );

        $range =
            'A1:'
            . $lastColumn
            . '1';

        $sheet
            ->getStyle(
                $range
            )
            ->getFont()
            ->setBold(
                true
            );

        $sheet
            ->getStyle(
                $range
            )
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet
            ->getStyle(
                $range
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

    /*
    |--------------------------------------------------------------------------
    | Write Member
    |--------------------------------------------------------------------------
    */

    private function writeMemberRow(
        mixed $sheet,
        int $rowNumber,
        mixed $member,
        array $contributions,
        bool $isExited
    ): void {
        $employerId =
            (int) $member->employer_id;

        $employerContributions =
            $contributions[
                $employerId
            ]
            ??
            [];

        $latest =
            $this->latestContribution(
                $employerContributions
            );

        $currentSalary =
            $latest !== null
                ? (
                    (float) (
                        $latest[
                            'basic_pay'
                        ]
                        ??
                        0
                    )
                )
                : null;

        $service =
            $this->pensionableService(
                joined:
                    $member->date_joined_fund,

                exitDate:
                    $isExited
                        ? $member->exit_date
                        : null
            );

        $values = [
            $member->employer_number,
            $member->penad_employer_number,
            $member->employer_name,
            $member->id,
            $member->member_number,
            $member->penad_member_number,
            $member->fundworx_member_number,
            $member->staff_number,
            $member->surname,

            trim(
                ($member->first_names ?? '')
                . ' '
                . ($member->other_names ?? '')
            ),

            $member->national_id,

            $this->dateValue(
                $member->date_of_birth
            ),

            $this->dateValue(
                $member->date_joined_fund
            ),

            $this->dateValue(
                $member->date_joined_employer
            ),

            $isExited
                ? $this->dateValue(
                    $member->exit_date
                )
                : null,

            $isExited
                ? $member->exit_reason
                : null,

            $service,

            $member->gender,

            ucfirst(
                strtolower(
                    (string) $member->membership_status
                )
            ),

            $this->displayFinancial(
                $currentSalary
            ),

            $this->displayFinancial(
                $currentSalary !== null
                    ? $currentSalary * 12
                    : null
            ),
        ];

        /*
        |--------------------------------------------------------------------------
        | Totals
        |--------------------------------------------------------------------------
        */

        $totalEmployee = 0.0;
        $totalEmployeeAvc = 0.0;
        $totalEmployer = 0.0;
        $totalEmployerAvc = 0.0;

        $hasAnyFinancialPeriod =
            false;

        /*
        |--------------------------------------------------------------------------
        | Monthly Financial Data
        |--------------------------------------------------------------------------
        */

        foreach (
            $this->months
            as $month
        ) {
            $data =
                $employerContributions[
                    $month[
                        'key'
                    ]
                ]
                ??
                null;

            /*
            |--------------------------------------------------------------------------
            | No Contribution Record For Month
            |--------------------------------------------------------------------------
            |
            | Blank stays blank.
            |
            */

            if ($data === null) {
                $values[] = null;
                $values[] = null;
                $values[] = null;
                $values[] = null;
                $values[] = null;

                continue;
            }

            $hasAnyFinancialPeriod =
                true;

            $salary =
                (float) (
                    $data[
                        'basic_pay'
                    ]
                    ??
                    0
                );

            $employee =
                (float) (
                    $data[
                        'employee_contribution'
                    ]
                    ??
                    0
                );

            $employeeAvc =
                (float) (
                    $data[
                        'employee_avc'
                    ]
                    ??
                    0
                );

            $employer =
                (float) (
                    $data[
                        'employer_contribution'
                    ]
                    ??
                    0
                );

            $employerAvc =
                (float) (
                    $data[
                        'employer_avc'
                    ]
                    ??
                    0
                );

            $values[] =
                $this->displayFinancial(
                    $salary
                );

            $values[] =
                $this->displayFinancial(
                    $employee
                );

            $values[] =
                $this->displayFinancial(
                    $employeeAvc
                );

            $values[] =
                $this->displayFinancial(
                    $employer
                );

            $values[] =
                $this->displayFinancial(
                    $employerAvc
                );

            $totalEmployee +=
                $employee;

            $totalEmployeeAvc +=
                $employeeAvc;

            $totalEmployer +=
                $employer;

            $totalEmployerAvc +=
                $employerAvc;
        }

        /*
        |--------------------------------------------------------------------------
        | Contribution Totals
        |--------------------------------------------------------------------------
        */

        $values[] =
            $this->displayFinancial(
                $hasAnyFinancialPeriod
                    ? $totalEmployee
                    : null
            );

        $values[] =
            $this->displayFinancial(
                $hasAnyFinancialPeriod
                    ? $totalEmployeeAvc
                    : null
            );

        $values[] =
            $this->displayFinancial(
                $hasAnyFinancialPeriod
                    ? $totalEmployer
                    : null
            );

        $values[] =
            $this->displayFinancial(
                $hasAnyFinancialPeriod
                    ? $totalEmployerAvc
                    : null
            );

        /*
        |--------------------------------------------------------------------------
        | Benefit Fields
        |--------------------------------------------------------------------------
        */

        $values[] =
            null; // Exit Benefit Paid

        $values[] =
            null; // Date Of Payment

        $values[] =
            null; // Benefit Outstanding

        $values[] =
            now()->format(
                'd/m/Y'
            );

        $values[] =
            null; // Transaction Date

        /*
        |--------------------------------------------------------------------------
        | Write Cells
        |--------------------------------------------------------------------------
        */

        foreach (
            $values
            as $index => $value
        ) {
            $column =
                Coordinate::stringFromColumnIndex(
                    $index + 1
                );

            $sheet->setCellValue(
                $column
                . $rowNumber,
                $value
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Active And Contributing
    |--------------------------------------------------------------------------
    |
    | The contribution array supplied to this method already contains ONLY
    | transactions inside the selected actuarial period.
    |
    | Therefore any positive EE/ER contribution or AVC in any selected month
    | means that the Active member is "Active and Contributing".
    |
    */

    private function hasContributionInSelectedPeriod(
        mixed $member,
        array $contributions
    ): bool {
        $employerId =
            (int) $member->employer_id;

        $periods =
            $contributions[
                $employerId
            ]
            ??
            [];

        foreach (
            $periods
            as $data
        ) {
            $total =
                (float) (
                    $data[
                        'employee_contribution'
                    ]
                    ??
                    0
                )
                +
                (float) (
                    $data[
                        'employee_avc'
                    ]
                    ??
                    0
                )
                +
                (float) (
                    $data[
                        'employer_contribution'
                    ]
                    ??
                    0
                )
                +
                (float) (
                    $data[
                        'employer_avc'
                    ]
                    ??
                    0
                );

            if ($total > 0) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Latest Contribution
    |--------------------------------------------------------------------------
    */

    private function latestContribution(
        array $contributions
    ): ?array {
        if (empty($contributions)) {
            return null;
        }

        ksort(
            $contributions
        );

        $latest =
            end(
                $contributions
            );

        return is_array(
            $latest
        )
            ? $latest
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Months
    |--------------------------------------------------------------------------
    */

    private function buildMonths(
        Carbon $from,
        Carbon $to
    ): array {
        $months = [];

        $period =
            CarbonPeriod::create(
                $from
                    ->copy()
                    ->startOfMonth(),

                '1 month',

                $to
                    ->copy()
                    ->startOfMonth()
            );

        foreach (
            $period
            as $date
        ) {
            $date =
                Carbon::instance(
                    $date
                )->startOfMonth();

            $months[] = [
                'key' =>
                    $date->format(
                        'Y-m'
                    ),

                'date' =>
                    $date,
            ];
        }

        return $months;
    }

    /*
    |--------------------------------------------------------------------------
    | Pensionable Service
    |--------------------------------------------------------------------------
    */

    private function pensionableService(
        mixed $joined,
        mixed $exitDate = null
    ): ?string {
        if (!$joined) {
            return null;
        }

        try {
            $start =
                Carbon::parse(
                    $joined
                );

            /*
            |--------------------------------------------------------------------------
            | Service End
            |--------------------------------------------------------------------------
            |
            | Exited member:
            |     actual exit date
            |
            | Active member:
            |     selected actuarial period end
            |
            */

            if ($exitDate) {
                $end =
                    Carbon::parse(
                        $exitDate
                    );
            } else {
                $lastMonth =
                    end(
                        $this->months
                    );

                $end =
                    Carbon::parse(
                        $lastMonth[
                            'date'
                        ]
                    )->endOfMonth();
            }

            if (
                $start->greaterThan(
                    $end
                )
            ) {
                return '0 years 0 months';
            }

            $years =
                $start->diffInYears(
                    $end
                );

            $months =
                $start
                    ->copy()
                    ->addYears(
                        $years
                    )
                    ->diffInMonths(
                        $end
                    );

            return
                $years
                . ' years '
                . $months
                . ' months';

        } catch (\Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Date Value
    |--------------------------------------------------------------------------
    */

    private function dateValue(
        mixed $value
    ): ?string {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse(
                $value
            )->format(
                'd/m/Y'
            );

        } catch (\Throwable) {
            return null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Financial Display
    |--------------------------------------------------------------------------
    |
    | NULL:
    |     blank
    |
    | Zero:
    |     -
    |
    | Non-zero:
    |     numeric value
    |
    */

    private function displayFinancial(
        mixed $value
    ): mixed {
        if ($value === null) {
            return null;
        }

        $number =
            (float) $value;

        if (
            abs($number)
            <
            0.0000001
        ) {
            return '-';
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Batch Column Exists
    |--------------------------------------------------------------------------
    |
    | Allows this service to work before/after the new contributing-member
    | counter migration is added.
    |
    */

    private function batchColumnExists(
        string $column
    ): bool {
        static $columns = null;

        if ($columns === null) {
            $columns =
                DB::getSchemaBuilder()
                    ->getColumnListing(
                        'actuarial_data_extract_batches'
                    );
        }

        return in_array(
            $column,
            $columns,
            true
        );
    }
}