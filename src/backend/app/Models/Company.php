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
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public const COL_ID = 'id';

    // has many Users
    // public function users()
    // {
    //     return $this->hasMany(User::class);
    // }

    /** @return HasOne<CompanyDatabase, $this> */
    public function database(): HasOne {
        return $this->hasOne(CompanyDatabase::class);
    }

    /** @return HasMany<DatabaseProxy, $this> */
    public function databaseProxies(): HasMany {
        return $this->hasMany(DatabaseProxy::class);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    /** @return HasMany<CompanyJob, $this> */
    public function jobs(): HasMany {
        return $this->hasMany(CompanyJob::class);
    }

    /**
     * Get the protected page password with deprecation warning.
     *
     * @return string|null
     *
     * @deprecated Use main_settings.protected_page_password instead
     */
    public function getProtectedPagePasswordAttribute(mixed $value): ?string {
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
    public function setProtectedPagePasswordAttribute(mixed $value): void {
        trigger_error(
            'Company::protected_page_password is deprecated. Use MainSettings.protected_page_password instead.',
            E_USER_DEPRECATED
        );

        $this->attributes['protected_page_password'] = $value;
    }
}
