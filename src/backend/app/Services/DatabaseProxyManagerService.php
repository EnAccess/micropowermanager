<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyDatabase;
use App\Models\DatabaseProxy;
use App\Utils\DemoCompany;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;

class DatabaseProxyManagerService {
    public function __construct(
        private DatabaseProxy $databaseProxy,
        private DatabaseManager $databaseManager,
        private CompanyDatabase $companyDatabase,
    ) {}

    public function findByEmail(string $email): DatabaseProxy {
        return $this->databaseProxy->findByEmail($email);
    }

    public function runForCompany(int $companyId, callable $callable): mixed {
        $database = $this->companyDatabase->findByCompanyId($companyId);
        $this->buildDatabaseConnection($database->getDatabaseName());

        return $callable();
    }

    /**
     * @return Builder<CompanyDatabase>
     */
    public function queryAllConnections(): Builder {
        return $this->companyDatabase->newQuery();
    }

    public function buildDatabaseConnectionDemoCompany(): void {
        $this->buildDatabaseConnection(DemoCompany::DEMO_COMPANY_DATABASE_NAME);
    }

    private function buildDatabaseConnection(string $databaseName): void {
        if (!app()->environment('testing')) {
            $databaseConnections = config('database.connections');
            $databaseConnections['tenant'] = [
                'driver' => 'mysql',
                'host' => $databaseConnections['micro_power_manager']['host'],
                'port' => $databaseConnections['micro_power_manager']['port'],
                'database' => $databaseName,
                'username' => $databaseConnections['micro_power_manager']['username'],
                'password' => $databaseConnections['micro_power_manager']['password'],
                'unix_socket' => $databaseConnections['micro_power_manager']['unix_socket'],
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ];
            config()->set('database.connections', $databaseConnections);
            $this->databaseManager->purge('tenant');
            $this->databaseManager->reconnect('tenant');
        }
    }
}
