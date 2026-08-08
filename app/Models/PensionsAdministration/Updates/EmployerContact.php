<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployerContact extends Model
{
    protected $fillable = [
        'employer_id',
        'title',
        'first_names',
        'surname',
        'position',
        'email',
        'telephone',
        'cell_number',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employer(): BelongsTo
    {
        return $this->belongsTo(
            Employer::class
        );
    }
}