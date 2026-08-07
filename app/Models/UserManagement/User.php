<?php

namespace App\Models\UserManagement;

use App\Models\Audit\AuditTrail;
use App\Models\Audit\UserSession;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;

    protected string $guard_name = 'web';

    protected $fillable = [
        'employee_number',
        'title',
        'first_name',
        'middle_name',
        'surname',
        'username',
        'email',
        'work_email',
        'cell_number',
        'telephone_number',
        'phone_extension',
        'organisation_unit_id',
        'job_title_id',
        'grade_id',
        'reports_to_user_id',
        'employment_date',
        'termination_date',
        'employment_status',
        'account_status',
        'password',
        'must_change_password',
        'temporary_password',
        'password_changed_at',
        'password_expires_at',
        'failed_login_attempts',
        'locked_at',
        'lock_expires_at',
        'last_login_at',
        'last_login_ip',
        'is_system_administrator',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'employment_date' => 'date',
            'termination_date' => 'date',
            'email_verified_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'password_expires_at' => 'datetime',
            'locked_at' => 'datetime',
            'lock_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'must_change_password' => 'boolean',
            'temporary_password' => 'boolean',
            'is_system_administrator' => 'boolean',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function organisationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganisationUnit::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reports_to_user_id');
    }

    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'reports_to_user_id');
    }

    public function dashboards(): BelongsToMany
    {
        return $this->belongsToMany(
            Dashboard::class,
            'dashboard_user',
            'user_id',
            'dashboard_id'
        )->withPivot(['is_default'])->withTimestamps();
    }

    public function passwordHistory(): HasMany
    {
        return $this->hasMany(PasswordHistory::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function auditTrails(): HasMany
    {
        return $this->hasMany(AuditTrail::class, 'user_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim(
            "{$this->title} {$this->first_name} {$this->middle_name} {$this->surname}"
        );
    }

    public function isAccountActive(): bool
    {
        return $this->is_active
            && $this->employment_status === 'active'
            && $this->account_status === 'active';
    }

    public function passwordHasExpired(): bool
    {
        return $this->password_expires_at !== null
            && now()->greaterThanOrEqualTo($this->password_expires_at);
    }

    public function requiresPasswordChange(): bool
    {
        return $this->must_change_password
            || $this->temporary_password
            || $this->passwordHasExpired();
    }
}