<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int         $id
 * @property string      $name;
 * @property string|null $protected_page_password DEPRECATED: Use MainSettings.protected_page_password instead
 */
class Company extends BaseModelCentral {
    use HasFactory;

    public const COL_ID = 'id';

    // has many Users
    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    public function database(): HasOne {
        return $this->hasOne(CompanyDatabase::class);
    }

    public function databaseProxies(): HasMany {
        return $this->hasMany(DatabaseProxy::class);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function jobs() {
        return $this->hasMany(CompanyJob::class);
    }

    /**
     * Get the protected page password with deprecation warning.
     *
     * @return string|null
     *
     * @deprecated Use main_settings.protected_page_password instead
     */
    public function getProtectedPagePasswordAttribute($value) {
        if ($value !== null) {
            trigger_error(
                'Company::protected_page_password is deprecated. Use MainSettings.protected_page_password instead.',
                E_USER_DEPRECATED
            );
        }

        return $value;
    }

    /**
     * Set the protected page password with deprecation warning.
     *
     * @param string|null $value
     *
     * @deprecated Use main_settings.protected_page_password instead
     */
    public function setProtectedPagePasswordAttribute($value) {
        trigger_error(
            'Company::protected_page_password is deprecated. Use MainSettings.protected_page_password instead.',
            E_USER_DEPRECATED
        );

        $this->attributes['protected_page_password'] = $value;
    }
}
