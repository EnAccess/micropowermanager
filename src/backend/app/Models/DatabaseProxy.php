<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @property      int                  $id
 * @property      string               $email
 * @property      int                  $fk_company_id
 * @property      int                  $fk_company_database_id
 * @property      Carbon|null          $created_at
 * @property      Carbon|null          $updated_at
 * @property-read Company|null         $company
 * @property-read CompanyDatabase|null $companyDatabase
 */
class DatabaseProxy extends BaseModelCentral {
    public const COL_DATABASE_CONNECTION = 'database_connection';
    public const COL_COMPANY_ID = 'fk_company_id';
    public const COL_EMAIL = 'email';
    private const CACHE_KEY_PREFIX = 'database_proxy';
    private const CACHE_TTL = 3600 * 24; // 24 hours in seconds

    protected static function booted(): void {
        static::saved(function (self $databaseProxy) {
            $databaseProxy->clearCache();
        });

        static::deleted(function (self $databaseProxy) {
            $databaseProxy->clearCache();
        });
    }

    /**
     * @return Builder<DatabaseProxy>
     */
    private function buildQuery(?int $companyId = null): Builder {
        $query = $this->newQuery();

        if ($companyId) {
            $query->where(self::COL_COMPANY_ID, '=', $companyId);
        }

        return $query;
    }

    public function findByEmail(string $email): DatabaseProxy {
        $cacheKey = self::CACHE_KEY_PREFIX.':'.md5($email);

        return Cache::remember($cacheKey, self::CACHE_TTL, fn () => $this->buildQuery()
            ->join(CompanyDatabase::TABLE_NAME, CompanyDatabase::COL_COMPANY_ID, '=', self::COL_COMPANY_ID)
            ->where(self::COL_EMAIL, '=', $email)
            ->firstOrFail());
    }

    public function findByCompanyId(int $companyId): DatabaseProxy {
        return $this->buildQuery($companyId)
            ->select(CompanyDatabase::COL_DATABASE_NAME)
            ->firstOrFail();
    }

    public function getCompanyId(): int {
        return $this->fk_company_id;
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo {
        return $this->belongsTo(Company::class, 'fk_company_id');
    }

    /**
     * @return BelongsTo<CompanyDatabase, $this>
     */
    public function companyDatabase(): BelongsTo {
        return $this->belongsTo(CompanyDatabase::class, 'fk_company_database_id');
    }

    private function clearCache(): void {
        $cacheKey = self::CACHE_KEY_PREFIX.':'.md5($this->email);
        Cache::forget($cacheKey);
    }
}
