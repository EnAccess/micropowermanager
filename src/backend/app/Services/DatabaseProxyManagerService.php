<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CompanyDatabase;
use App\Models\DatabaseProxy;
use App\Utils\DemoCompany;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
        if (!app()->environment('testing')) {
            $database = $this->companyDatabase->findByCompanyId($companyId);
            $this->buildDatabaseConnection($database->database_name);
        }

        return $callable();
    }

    /**
     * @return Builder<CompanyDatabase>
     */
    public function queryAllConnections(): Builder {
        return $this->companyDatabase->newQuery();
    }

    /**
     * Runs the callable once per tenant, each time with that tenant's connection
     * bound, and leaves the tenant binding as it found it.
     *
     * Binding a tenant connection never restores the previous one, so without the
     * restore below a later query would silently read whichever company happened
     * to run last.
     *
     * @param callable(int): void                  $callable
     * @param callable(int, \Throwable): void|null $onError  re-throws when null, so
     *                                                       callers opt in to
     *                                                       per-tenant degradation
     */
    public function eachCompany(callable $callable, ?callable $onError = null): void {
        $tenantConnectionBeforeLoop = config('database.connections.tenant');

        try {
            $this->queryAllConnections()->chunkById(50, function (Collection $companyDatabases) use (
                $callable,
                $onError
            ): void {
                /* @var Collection<int, CompanyDatabase> $companyDatabases */
                foreach ($companyDatabases as $companyDatabase) {
                    $companyId = $companyDatabase->company_id;

                    try {
                        $this->runForCompany($companyId, fn () => $callable($companyId));
                    } catch (\Throwable $throwable) {
                        if ($onError === null) {
                            throw $throwable;
                        }

                        $onError($companyId, $throwable);
                    }
                }
            });
        } finally {
            $this->restoreTenantConnection($tenantConnectionBeforeLoop);
        }
    }

    public function buildDatabaseConnectionDemoCompany(): void {
        $this->buildDatabaseConnection(DemoCompany::DEMO_COMPANY_DATABASE_NAME);
    }

    public function buildDatabaseConnectionTestCompany(?string $testDatabaseName): void {
        $this->buildDatabaseConnection($testDatabaseName ?? 'TestCompany_1');
    }

    /** @param array<string, mixed>|null $tenantConnection */
    private function restoreTenantConnection(?array $tenantConnection): void {
        $databaseConnections = config('database.connections');

        if ($tenantConnection === null) {
            unset($databaseConnections['tenant']);
        } else {
            $databaseConnections['tenant'] = $tenantConnection;
        }

        config()->set('database.connections', $databaseConnections);
        $this->databaseManager->purge('tenant');
    }

    private function buildDatabaseConnection(string $databaseName): void {
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
