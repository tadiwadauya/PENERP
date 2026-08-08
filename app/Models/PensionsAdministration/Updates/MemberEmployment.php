<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberEmployment extends Model
{
    protected $fillable = [
        'member_id',
        'employer_id',

        'staff_number',
        'vote_number',

        'branch',
        'department',

        'date_joined_employer',
        'effective_from',
        'effective_to',

        'employment_status',
        'is_current',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_joined_employer' => 'date',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_current' => 'boolean',
    ];

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
}