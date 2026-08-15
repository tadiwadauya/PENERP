<?php

namespace App\Models\PensionsAdministration\Contributions;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\PensionsAdministration\Updates\Member;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContributionPeriodMemberStatus extends Model
{
    protected $fillable = [
        'contribution_period_id',

        'member_id',
        'employer_id',

        'contribution_status',

        'reason',

        'import_batch_id',
    ];


    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(
            ContributionPeriod::class,
            'contribution_period_id'
        );
    }


    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class,
            'member_id'
        );
    }


    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class,
            'employer_id'
        );
    }


    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(
            ContributionImportBatch::class,
            'import_batch_id'
        );
    }
}