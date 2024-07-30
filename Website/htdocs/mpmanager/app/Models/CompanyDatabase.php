<?php

namespace App\Models;

use App\Models\Base\MasterModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id;
 * @property int $database_name;
 * @property int $company_id;
 */
class CompanyDatabase extends MasterModel
{
    use HasFactory;

    public const TABLE_NAME = 'company_databases';
    public const COL_DATABASE_NAME = 'database_name';
    public const COL_COMPANY_ID = 'company_id';

    public function company(): HasOne
    {
        return $this->HasOne(Company::class);
    }

    public function findByCompanyId(int $companyId): CompanyDatabase
    {
        /** @var CompanyDatabase $result */
        $result = $this->newQuery()
            ->select(self::COL_DATABASE_NAME)
            ->where(self::COL_COMPANY_ID, '=', $companyId)
            ->firstOrFail();

        return $result;
    }

    public function getDatabaseName(): string
    {
        return $this->database_name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }
}
