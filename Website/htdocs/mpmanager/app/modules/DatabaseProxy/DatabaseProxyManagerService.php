<?php

declare(strict_types = 1);

namespace MPM\DatabaseProxy;

use App\Models\DatabaseProxy;


class DatabaseProxyManagerService
{

    public function __construct(private DatabaseProxy $databaseProxy)
    {
    }

    public function findByEmail(string $email): string
    {
        return $this->databaseProxy->findByEmail($email)[DatabaseProxy::COL_DATABASE_CONNECTION];
    }

    public function findCompanyId(int $companyId): string
    {
        return $this->databaseProxy->findByCompanyId($companyId)[DatabaseProxy::COL_DATABASE_CONNECTION];
    }
}
