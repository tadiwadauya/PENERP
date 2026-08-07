<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Grade extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'rank_order',
        'is_management',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_management' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jobTitles(): HasMany
    {
        return $this->hasMany(
            JobTitle::class,
            'default_grade_id'
        );
    }
}