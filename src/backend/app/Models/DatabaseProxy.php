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

    private function buildQuery(?int $companyId = null): Builder {
        $query = $this->newQuery();

        if ($companyId) {
            $query->where(self::COL_COMPANY_ID, '=', $companyId);
        }

        return $query;
    }

    public function findByEmail(string $email): DatabaseProxy {
        /** @var DatabaseProxy $result */
        $result = $this->buildQuery()
            ->join(CompanyDatabase::TABLE_NAME, CompanyDatabase::COL_COMPANY_ID, '=', self::COL_COMPANY_ID)
            ->where(self::COL_EMAIL, '=', $email)
            ->firstOrFail();

        return $result;
    }

    public function findByCompanyId(int $companyId): DatabaseProxy {
        /** @var DatabaseProxy $result */
        $result = $this->buildQuery($companyId)
            ->select(CompanyDatabase::COL_DATABASE_NAME)
            ->firstOrFail();

        return $result;
    }

    public function getCompanyId(): int {
        return $this->fk_company_id;
    }

    public function company(): BelongsTo {
        return $this->belongsTo(Company::class, 'fk_company_id');
    }

    public function companyDatabase(): BelongsTo {
        return $this->belongsTo(CompanyDatabase::class, 'fk_company_database_id');
    }
}
