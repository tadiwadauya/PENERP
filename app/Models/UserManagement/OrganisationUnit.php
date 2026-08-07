<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrganisationUnit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'unit_type',
        'parent_id',
        'dashboard_id',
        'email',
        'telephone',
        'physical_location',
        'is_active',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('display_order');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function jobTitles(): HasMany
    {
        return $this->hasMany(
            JobTitle::class,
            'default_organisation_unit_id'
        );
    }
}