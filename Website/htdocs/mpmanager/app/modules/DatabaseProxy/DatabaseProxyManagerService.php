<?php

declare(strict_types = 1);

namespace MPM\DatabaseProxy;

use App\Models\CompanyDatabase;
use App\Models\DatabaseProxy;


class DatabaseProxyManagerService
{

    public function __construct(private DatabaseProxy $databaseProxy)
    {
    }

    public function findByEmail(string $email): string
    {
        return $this->databaseProxy->findByEmail($email)[CompanyDatabase::COL_DATABASE_NAME];
    }

    public function findCompanyId(int $companyId): string
    {
        return $this->databaseProxy->findByCompanyId($companyId)[DatabaseProxy::COL_DATABASE_CONNECTION];
    }
}
