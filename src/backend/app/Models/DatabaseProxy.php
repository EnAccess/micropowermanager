<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\BaseModelCentral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $fk_company_id
 */
class DatabaseProxy extends BaseModelCentral {
    public const COL_DATABASE_CONNECTION = 'database_connection';
    public const COL_COMPANY_ID = 'fk_company_id';
    public const COL_EMAIL = 'email';

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
        return $this->buildQuery()
            ->join(CompanyDatabase::TABLE_NAME, CompanyDatabase::COL_COMPANY_ID, '=', self::COL_COMPANY_ID)
            ->where(self::COL_EMAIL, '=', $email)
            ->firstOrFail();
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
}
