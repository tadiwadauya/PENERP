<?php

namespace App\Models\PensionsAdministration\Reports;

use App\Models\PensionsAdministration\Updates\Employer;
use App\Models\UserManagement\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActuarialDataExtractBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'date_from',
        'date_to',
        'employer_id',
        'status',
        'progress_percentage',
        'active_members',
        'nil_contributors',
        'exited_members',
        'file_path',
        'file_name',
        'failure_reason',
        'requested_by',
        'processing_started_at',
        'completed_at',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'progress_percentage' => 'decimal:2',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(Employer::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
