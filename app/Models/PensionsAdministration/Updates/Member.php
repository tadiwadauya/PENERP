<?php

namespace App\Models\PensionsAdministration\Updates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Member extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'member_number',
        'penad_member_number',
        'fundworx_member_number',

        'title',
        'surname',
        'first_names',

        'national_id',
        'national_id_normalized',

        'date_of_birth',
        'gender',
        'marital_status',
        'occupation',

        'email',
        'secondary_email',
        'cell_number',
        'secondary_cell_number',

        'physical_address_1',
        'physical_address_2',
        'physical_address_3',
        'physical_suburb',
        'physical_city',
        'physical_country',
        'marital_status',
        'cellphone_number',
        'email_address',
        'home_address',

        'postal_address_1',
        'postal_address_2',
        'postal_address_3',
        'postal_city',
        'postal_country',

        'date_joined_fund',

        'membership_status',
        'is_active',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined_fund' => 'date',
        'is_active' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function employments(): HasMany
    {
        return $this->hasMany(
            MemberEmployment::class
        );
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(
            MemberStatusHistory::class
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Current Employment
    |--------------------------------------------------------------------------
    */

    public function currentEmployment()
    {
        return $this->hasOne(
            MemberEmployment::class
        )->where(
            'is_current',
            true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    */

    public function getFullNameAttribute(): string
    {
        return trim(
            $this->surname
            . ', '
            . $this->first_names
        );
    }


    /*
    |--------------------------------------------------------------------------
    | National ID Normalisation
    |--------------------------------------------------------------------------
    */

    public static function normalizeNationalId(
        ?string $nationalId
    ): ?string {
        if (!$nationalId) {
            return null;
        }

        $value =
            strtoupper(
                trim($nationalId)
            );

        $value =
            preg_replace(
                '/[^A-Z0-9]/',
                '',
                $value
            );

        return $value ?: null;
    }
}