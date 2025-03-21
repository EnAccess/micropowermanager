<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyDatabase;
use App\Models\User;
use App\Services\Interfaces\IBaseService;
use App\Utils\DemoCompany;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * @implements IBaseService<Company>
 */
class CompanyService implements IBaseService {
    public function __construct(
        private Company $company,
        private CompanyDatabase $companyDatabase,
        private UserService $userService,
        private DatabaseManager $databaseManager,
    ) {}

    public function getByName($name): Company {
        return $this->company->where('name', $name)->firstOrFail();
    }

    public function getById($id): Company {
        $result = $this->company->newQuery()->findOrFail($id);

        return $result;
    }

    public function create($data): Company {
        $company = $this->company->newQuery()->create($data);

        return $company;
    }

    public function update($model, array $data): Company {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        return $this->company->newQuery()->get();
    }

    public function findByEmail(string $email): User {
        return $this->userService->findByEmail($email);
    }

    public function runForCompany(int $companyId, callable $callable) {
        $database = $this->companyDatabase->findByCompanyId($companyId);
        $this->buildDatabaseConnection($database->getDatabaseName());

        return $callable();
    }

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
