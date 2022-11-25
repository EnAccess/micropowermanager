<?php

declare(strict_types=1);

namespace MPM\DatabaseProxy;

use App\Models\CompanyDatabase;
use App\Models\DatabaseProxy;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class DatabaseProxyManagerService
{
    public function __construct(private DatabaseProxy $databaseProxy, private DatabaseManager $databaseManager, private CompanyDatabase $companyDatabase)
    {
    }

    public function findByEmail(string $email): DatabaseProxy
    {
        return $this->databaseProxy->findByEmail($email);
    }


    public function runForCompany(int $companyId, callable $callable)
    {
        $database = $this->companyDatabase->findByCompanyId($companyId);
        $this->buildDatabaseConnection($database->getDatabaseName());

        return $callable();
    }

    public function queryAllConnections(): Builder
    {
        return $this->companyDatabase->newQuery();
    }


    private function buildDatabaseConnection(string $databaseName): void
    {
        Log::info('Building database connection for ' . $databaseName);
        $databaseConnections = config()->get('database.connections');
        $databaseConnections['shard'] = [
            'driver' => 'mysql',
            'host' => 'db',
            'port' => '3306',
            'database' => $databaseName,
            'username' => 'root',
            'password' => 'inensus2022.',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => false,
            'engine' => null,
        ];
        config()->set('database.connections', $databaseConnections);
        $this->databaseManager->purge('shard');
        $this->databaseManager->reconnect('shard');
    }
}
