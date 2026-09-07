<?php

namespace App\Services\PensionsAdministration\BenefitStatements;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BenefitStatementContributionService
{
    public function calculate(int $memberId,int $employerId,Carbon|string $statementDate): array
    {
        $statementDate=Carbon::parse($statementDate)->endOfDay();
        $statementYear=(int)$statementDate->year;
        $statementMonth=(int)$statementDate->month;

        $availableColumns=array_flip(Schema::getColumnListing('member_contributions'));

        $records=DB::table('member_contributions as mc')
            ->where('mc.member_id',$memberId)
            ->where('mc.employer_id',$employerId)
            ->whereIn('mc.transaction_type',['expected','take_on'])
            ->where(function($query) use($statementYear,$statementMonth): void {
                $query->where('mc.period_year','<',$statementYear)
                    ->orWhere(function($query) use($statementYear,$statementMonth): void {
                        $query->where('mc.period_year',$statementYear)
                            ->where('mc.period_month','<=',$statementMonth);
                    });
            })
            ->orderBy('mc.period_year')
            ->orderBy('mc.period_month')
            ->orderBy('mc.id')
            ->select($this->selectColumns($availableColumns))
            ->get();

        $openingBalance=$this->zeroBalance();

        $months=[];
        for ($month=1;$month<=12;$month++) {
            $months[$month]=$this->zeroBalance();
        }

        $yearTotals=$this->zeroBalance();
        $closingBalance=$this->zeroBalance();
        $salaryByContributionMonth=[];

        foreach ($records as $record) {
            $year=(int)$record->period_year;
            $month=(int)$record->period_month;

            if ($month<1 || $month>12) continue;

            $values=$this->resolveZwgValues($record,$availableColumns);

            // Opening balance = all accumulated contribution values before the statement year.
            if ($year<$statementYear) {
                $this->addBalance($openingBalance,$values);
                $this->addBalance($closingBalance,$values);
                $this->captureSalaryMonth($salaryByContributionMonth,$record,$values);
                continue;
            }

            // Current statement year values only up to the selected statement month.
            if ($year===$statementYear && $month<=$statementMonth) {
                $this->addBalance($months[$month],$values);
                $this->addBalance($yearTotals,$values);
                $this->addBalance($closingBalance,$values);
                $this->captureSalaryMonth($salaryByContributionMonth,$record,$values);
            }
        }

        ksort($salaryByContributionMonth);

        // Current Annual Emoluments:
        // average salary of the member's last 3 actual contribution months
        // up to the selected statement date, annualised by multiplying by 12.
        $lastThreeContributionMonths=array_slice($salaryByContributionMonth,-3,null,true);
        $lastThreeContributionSalaries=array_values($lastThreeContributionMonths);

        $averageLastThreeMonthsSalary=count($lastThreeContributionSalaries)>0
            ? array_sum($lastThreeContributionSalaries)/count($lastThreeContributionSalaries)
            : 0.0;

        $currentAnnualEmoluments=$averageLastThreeMonthsSalary*12;
        $pensionableEmoluments=$currentAnnualEmoluments;

        $latestContributedSalary=!empty($salaryByContributionMonth)
            ? (float)end($salaryByContributionMonth)
            : 0.0;

        return [
            'opening_balance'=>$this->roundBalance($openingBalance),
            'months'=>array_map(fn(array $balance): array=>$this->roundBalance($balance),$months),
            'year_totals'=>$this->roundBalance($yearTotals),
            'closing_balance'=>$this->roundBalance($closingBalance),

            'latest_salary'=>round($latestContributedSalary,2),
            'average_last_three_months_salary'=>round($averageLastThreeMonthsSalary,2),
            'current_annual_emoluments'=>round($currentAnnualEmoluments,2),
            'pensionable_emoluments'=>round($pensionableEmoluments,2),

            // Diagnostic fields so we can verify which 3 months were used.
            'salary_months_used'=>array_keys($lastThreeContributionMonths),
            'salary_values_used'=>array_values($lastThreeContributionMonths),

            'record_count'=>$records->count(),
        ];
    }

    private function selectColumns(array $availableColumns): array
    {
        $columns=[
            'mc.id',
            'mc.member_id',
            'mc.employer_id',
            'mc.period_year',
            'mc.period_month',
            'mc.transaction_type',
        ];

        foreach ([
            'period_date',
            'source_system',

            'basic_pay',
            'employee_contribution',
            'employer_contribution',
            'employee_avc',
            'employer_avc',
            'employee_arrear',
            'employer_arrear',
            'employee_transfer_in',
            'employer_transfer_in',
            'employee_late_interest',
            'employer_late_interest',

            'zwg_basic_pay',
            'zwg_employee_contribution',
            'zwg_employer_contribution',
            'zwg_employee_avc',
            'zwg_employer_avc',
            'zwg_employee_arrear',
            'zwg_employer_arrear',
            'zwg_employee_transfer_in',
            'zwg_employer_transfer_in',
            'zwg_employee_late_interest',
            'zwg_employer_late_interest',
        ] as $column) {
            if (isset($availableColumns[$column])) $columns[]='mc.'.$column;
        }

        return $columns;
    }

    private function resolveZwgValues(object $record,array $availableColumns): array
    {
        $historical=strtolower(trim((string)($record->source_system??'')))==='historical_migration';

        return [
            'basic_pay'=>$this->resolveAmount($record,$availableColumns,'basic_pay','zwg_basic_pay',$historical),
            'employee_contribution'=>$this->resolveAmount($record,$availableColumns,'employee_contribution','zwg_employee_contribution',$historical),
            'employer_contribution'=>$this->resolveAmount($record,$availableColumns,'employer_contribution','zwg_employer_contribution',$historical),
            'employee_avc'=>$this->resolveAmount($record,$availableColumns,'employee_avc','zwg_employee_avc',$historical),
            'employer_avc'=>$this->resolveAmount($record,$availableColumns,'employer_avc','zwg_employer_avc',$historical),
            'employee_arrear'=>$this->resolveAmount($record,$availableColumns,'employee_arrear','zwg_employee_arrear',$historical),
            'employer_arrear'=>$this->resolveAmount($record,$availableColumns,'employer_arrear','zwg_employer_arrear',$historical),
            'employee_transfer_in'=>$this->resolveAmount($record,$availableColumns,'employee_transfer_in','zwg_employee_transfer_in',$historical),
            'employer_transfer_in'=>$this->resolveAmount($record,$availableColumns,'employer_transfer_in','zwg_employer_transfer_in',$historical),
            'employee_late_interest'=>$this->resolveAmount($record,$availableColumns,'employee_late_interest','zwg_employee_late_interest',$historical),
            'employer_late_interest'=>$this->resolveAmount($record,$availableColumns,'employer_late_interest','zwg_employer_late_interest',$historical),
        ];
    }

    private function resolveAmount(
        object $record,
        array $availableColumns,
        string $genericColumn,
        string $zwgColumn,
        bool $historical
    ): float {
        // Historical migration stores converted ZWG values in the generic columns.
        if ($historical) {
            return isset($availableColumns[$genericColumn])
                ? (float)($record->{$genericColumn}??0)
                : 0.0;
        }

        // New PENERP monthly uploads store ZWG values in zwg_* columns.
        // Check NULL, not zero: 0.0000 is a real contribution value.
        if (
            isset($availableColumns[$zwgColumn])
            && property_exists($record,$zwgColumn)
            && $record->{$zwgColumn}!==null
        ) {
            return (float)$record->{$zwgColumn};
        }

        return isset($availableColumns[$genericColumn])
            ? (float)($record->{$genericColumn}??0)
            : 0.0;
    }

    private function captureSalaryMonth(array &$salaryByContributionMonth,object $record,array $values): void
    {
        $salary=(float)$values['basic_pay'];

        // A month qualifies for the 3-month average only when the member
        // actually contributed and has a pensionable salary for that month.
        $hasContribution=
            (float)$values['employee_contribution']!==0.0
            ||
            (float)$values['employer_contribution']!==0.0
            ||
            (float)$values['employee_avc']!==0.0
            ||
            (float)$values['employer_avc']!==0.0;

        if ($salary<=0 || !$hasContribution) return;

        $key=sprintf('%04d-%02d',(int)$record->period_year,(int)$record->period_month);

        // If more than one contribution row exists for a month, keep the
        // last non-zero pensionable salary for that month.
        $salaryByContributionMonth[$key]=$salary;
    }

    private function addBalance(array &$target,array $values): void
    {
        foreach (array_keys($target) as $key) {
            $target[$key]+=(float)($values[$key]??0);
        }
    }

    private function zeroBalance(): array
    {
        return [
            'basic_pay'=>0.0,
            'employee_contribution'=>0.0,
            'employer_contribution'=>0.0,
            'employee_avc'=>0.0,
            'employer_avc'=>0.0,
            'employee_arrear'=>0.0,
            'employer_arrear'=>0.0,
            'employee_transfer_in'=>0.0,
            'employer_transfer_in'=>0.0,
            'employee_late_interest'=>0.0,
            'employer_late_interest'=>0.0,
        ];
    }

    private function roundBalance(array $balance): array
    {
        foreach ($balance as $key=>$value) {
            $balance[$key]=round((float)$value,2);
        }

        return $balance;
    }
}
