<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberContribution extends Model
{
    protected $fillable = [
        'member_id',
        'employer_id',
        'contribution_period_id',

        'import_batch_id',
        'import_row_id',

        'source_row_number',
        'source_system',

        'penerp_member_number',
        'penad_member_number',
        'fundworx_member_number',
        'staff_number',

        'period_date',
        'period_year',
        'period_month',
        'due_date',

        'scheme_code',
        'transaction_type',
        'payment_flag',

        'usd_basic_pay',
        'usd_employee_rate',
        'usd_employer_rate',
        'usd_employee_contribution',
        'usd_employer_contribution',
        'usd_employee_avc',
        'usd_employer_avc',
        'usd_employee_arrear',
        'usd_employer_arrear',
        'usd_employee_transfer_in',
        'usd_employer_transfer_in',
        'usd_employee_late_interest',
        'usd_employer_late_interest',

        'zwg_basic_pay',
        'zwg_employee_rate',
        'zwg_employer_rate',
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

        'comments',

        'posted_by',
        'posted_at',

        'created_by',
        'updated_by',
    ];


    protected function casts(): array
    {
        return [
            'period_date' =>
                'date',

            'due_date' =>
                'date',

            'posted_at' =>
                'datetime',
        ];
    }


    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class
        );
    }


    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class
        );
    }


    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(
            ContributionPeriod::class
        );
    }


    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(
            ContributionImportBatch::class,
            'import_batch_id'
        );
    }


    public function importRow(): BelongsTo
    {
        return $this->belongsTo(
            ContributionImportRow::class,
            'import_row_id'
        );
    }


    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }
}