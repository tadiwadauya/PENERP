<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployerGroup extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'vote_number_required',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'vote_number_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employers(): HasMany
    {
        return $this->hasMany(
            Employer::class,
            'employer_group_id'
        );
    }
}