<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanyDatabase extends HybridModel
{
    use HasFactory;

    public const TABLE_NAME = 'company_databases';
    public const COL_DATABASE_NAME = 'database_name';
    public const COL_COMPANY_ID = 'company_id';


    public function company(): HasOne
    {
        return $this->HasOne(Company::class);
    }


    public function getDatabaseConnectionName(int $companyId, ):string
    {
        $databaseName =  $this->newQuery()
            ->select(self::COL_DATABASE_NAME)
            ->where(Company::COL_ID, '=', $companyId)
            ->firstOrFail();

        return $databaseName[self::COL_DATABASE_NAME];
    }
}
