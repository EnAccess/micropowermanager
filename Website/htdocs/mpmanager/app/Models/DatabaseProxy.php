<?php

declare(strict_types=1);

namespace App\Models;

use Dflydev\DotAccessData\Data;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property  int $fk_company_id
 */
class DatabaseProxy extends MasterModel
{
    public const COL_DATABASE_CONNECTION = 'database_connection';
    public const COL_COMPANY_ID = 'fk_company_id';
    public const COL_EMAIL = 'email';

    private function buildQuery(?int $companyId = null): Builder
    {
        $query = $this->newQuery();

        if ($companyId) {
            $query->where(self::COL_COMPANY_ID, '=', $companyId);
        }

        return $query;
    }

    public function findByEmail(string $email): DatabaseProxy
    {
        /** @var DatabaseProxy $result */
        $result = $this->buildQuery()
            ->select(CompanyDatabase::COL_DATABASE_NAME)
            ->where(self::COL_EMAIL, '=', $email)
            ->firstOrFail();

        return $result;
    }

    public function findByCompanyId(int $companyId): DatabaseProxy
    {
        /** @var DatabaseProxy $result */
        $result =  $this->buildQuery($companyId)
            ->select(CompanyDatabase::COL_DATABASE_NAME)
            ->firstOrFail();

        return $result;
    }

    public function getCompanyId(): int
    {
        return $this->fk_company_id;
    }
}
