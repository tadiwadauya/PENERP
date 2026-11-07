<?php

namespace App\Services\PensionsAdministration\BenefitStatements;

use App\Models\PensionsAdministration\Updates\Member;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class BenefitStatementBulkEligibility
{
    public function query(int $employerId, Carbon $date): Builder
    {
        $year=(int)$date->year;
        $month=(int)$date->month;
        $columns=Schema::getColumnListing('member_contributions');
        $amountColumns=array_values(array_intersect($columns,[
            'employee_contribution','employer_contribution','employee_avc','employer_avc',
            'zwg_employee_contribution','zwg_employer_contribution','zwg_employee_avc','zwg_employer_avc',
        ]));
        if (!$amountColumns) throw new RuntimeException('No contribution amount columns exist.');

        return Member::query()
            ->whereRaw('LOWER(TRIM(membership_status)) = ?', ['active'])
            ->where(function ($q) use ($date) {
                $q->whereNull('exit_date')->orWhere('exit_date','>',$date->toDateString());
            })
            ->whereExists(function ($q) use ($employerId,$date) {
                $q->selectRaw('1')->from('member_employments as me')
                    ->whereColumn('me.member_id','members.id')->where('me.employer_id',$employerId)
                    ->where(function ($q) use ($date) {
                        $q->whereNull('me.effective_from')->orWhere('me.effective_from','<=',$date->toDateString());
                    })
                    ->where(function ($q) use ($date) {
                        $q->whereNull('me.effective_to')->orWhere('me.effective_to','>=',$date->toDateString());
                    });
            })
            ->whereExists(function ($q) use ($employerId,$year,$month,$amountColumns) {
                $q->selectRaw('1')->from('member_contributions as mc')
                    ->whereColumn('mc.member_id','members.id')->where('mc.employer_id',$employerId)
                    ->where('mc.transaction_type','expected')->where('mc.period_year',$year)
                    ->where('mc.period_month','<=',$month)
                    ->where(function ($q) use ($amountColumns) {
                        foreach ($amountColumns as $column) $q->orWhere('mc.'.$column,'>',0);
                    });
            });
    }
}
