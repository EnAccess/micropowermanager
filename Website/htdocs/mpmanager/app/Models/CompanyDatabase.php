<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CompanyDatabase extends BaseModel
{
    use HasFactory;
    protected $connection = 'micro_power_manager';


    public const COL_DATABASE_NAME = 'database_name';

    // has one company
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
