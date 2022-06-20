<?php

declare(strict_types = 1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DatabaseProxy extends Model
{
    public const COL_DATABASE_CONNECTION = 'database_connection';
    public const COL_COMPANY_ID = 'company_id';
    public const COL_EMAIL = 'email';

    private function buildQuery(?int $companyId = null): Builder
    {
        $query = $this->newQuery();

        if ($companyId) {
            $query->where(self::COL_COMPANY_ID, '=', $companyId);
        }

        return $query;
    }

    public function findByEmail(string $email): Model
    {
        return $this->buildQuery()
            ->select(self::COL_DATABASE_CONNECTION)
            ->where(self::COL_EMAIL, '=', $email)
            ->firstOrFail();
    }

    public function findByCompanyId(int $companyId): Model
    {
        return $this->buildQuery($companyId)
            ->select(self::COL_DATABASE_CONNECTION)
            ->firstOrFail();
    }
}
