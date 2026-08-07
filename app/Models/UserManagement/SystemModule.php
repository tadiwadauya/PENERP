<?php

namespace App\Models\UserManagement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SystemModule extends Model
{
    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'route_prefix',
        'icon',
        'description',
        'display_order',
        'show_in_sidebar',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'show_in_sidebar' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_id'
        );
    }

    public function children(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_id'
        );
    }
}