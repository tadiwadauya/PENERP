<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dashboard extends Model
{
    protected $fillable = [
        'code',
        'name',
        'route_name',
        'icon',
        'description',
        'display_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'dashboard_user'
        )
            ->withPivot([
                'is_default',
                'assigned_by',
            ])
            ->withTimestamps();
    }

    public function organisationUnits(): HasMany
    {
        return $this->hasMany(OrganisationUnit::class);
    }
}