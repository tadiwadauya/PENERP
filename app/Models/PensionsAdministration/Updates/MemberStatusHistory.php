<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStatusHistory extends Model
{
    protected $fillable = [
        'member_id',
        'old_status',
        'new_status',
        'effective_date',
        'movement_type',
        'reason',
        'source',
        'changed_by',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(
            Member::class
        );
    }
}