<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property      int                            $id
 * @property      string                         $name
 * @property      string                         $address
 * @property      string                         $phone
 * @property      int                            $country_id
 * @property      Carbon|null                    $created_at
 * @property      Carbon|null                    $updated_at
 * @property      string                         $email
 * @property-read Collection<int, ApiKey>        $apiKeys
 * @property-read CompanyDatabase|null           $database
 * @property-read Collection<int, DatabaseProxy> $databaseProxies
 */
class Company extends BaseModelCentral {
    /** @use HasFactory<CompanyFactory> */
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

    /** @return HasMany<ApiKey, $this> */
    public function apiKeys(): HasMany {
        return $this->hasMany(ApiKey::class);
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }
}
