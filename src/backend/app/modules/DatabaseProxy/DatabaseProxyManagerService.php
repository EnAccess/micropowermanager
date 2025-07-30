<?php

declare(strict_types=1);

namespace MPM\DatabaseProxy;

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

    public function runForCompany(int $companyId, callable $callable) {
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
        $databaseConnections = config()->get('database.connections');
        $databaseConnections['tenant'] = [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => $databaseName,
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
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
