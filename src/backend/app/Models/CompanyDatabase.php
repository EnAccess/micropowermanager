<?php

namespace App\Models;

use App\Models\Base\BaseModelCore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id;
 * @property int $database_name;
 * @property int $company_id;
 */
class CompanyDatabase extends BaseModelCore
{
    use HasFactory;

    public const TABLE_NAME = 'company_databases';
    public const COL_DATABASE_NAME = 'database_name';
    public const COL_COMPANY_ID = 'company_id';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function databaseProxies(): HasMany
    {
        return $this->hasMany(DatabaseProxy::class);
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
