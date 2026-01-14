<?php

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Database\Factories\CompanyDatabaseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property      int                            $id
 * @property      int                            $company_id
 * @property      string                         $database_name
 * @property      Carbon|null                    $created_at
 * @property      Carbon|null                    $updated_at
 * @property-read Company|null                   $company
 * @property-read Collection<int, DatabaseProxy> $databaseProxies
 */
class CompanyDatabase extends BaseModelCentral {
    /** @use HasFactory<CompanyDatabaseFactory> */
    use HasFactory;

    public const TABLE_NAME = 'company_databases';
    public const COL_DATABASE_NAME = 'database_name';
    public const COL_COMPANY_ID = 'company_id';
    private const CACHE_KEY_PREFIX = 'company_database';
    private const CACHE_TTL = 3600; // 1 hour in seconds

    protected static function booted(): void {
        static::saved(function (self $companyDatabase) {
            $companyDatabase->clearCache();
        });

        static::deleted(function (self $companyDatabase) {
            $companyDatabase->clearCache();
        });
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<DatabaseProxy, $this> */
    public function databaseProxies(): HasMany {
        return $this->hasMany(DatabaseProxy::class);
    }

    public function findByCompanyId(int $companyId): CompanyDatabase {
        $cacheKey = self::CACHE_KEY_PREFIX.':'.$companyId;

        return Cache::remember($cacheKey, self::CACHE_TTL, fn () => $this->newQuery()
            ->where(self::COL_COMPANY_ID, '=', $companyId)
            ->firstOrFail());
    }

    public function getDatabaseName(): string {
        return $this->database_name;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getCompanyId(): int {
        return $this->company_id;
    }

    private function clearCache(): void {
        $cacheKey = self::CACHE_KEY_PREFIX.':'.$this->company_id;
        Cache::forget($cacheKey);
    }
}
