<?php

namespace App\Services;

use App\Models\CompanyDatabase;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

/**
 * @implements IBaseService<CompanyDatabase>
 */
class CompanyDatabaseService implements IBaseService {
    public function __construct(
        private CompanyDatabase $companyDatabase,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {}

    public function getById(int $id): CompanyDatabase {
        $result = $this->companyDatabase->newQuery()->find($id);

        return $result;
    }

    public function create(array $data): CompanyDatabase {
        /** @var CompanyDatabase $result */
        $company_database = $this->companyDatabase->newQuery()->create($data);
        $database_name = $company_database->database_name;
        $company_id = $company_database->company_id;

        // Do we need to sanitise inputs here?
        // if (preg_match('/^[a-zA-Z0-9_]+$/', $database_name)) {
        //     DB::unprepared('CREATE DATABASE IF NOT EXISTS `'.addslashes($database_name).'`');
        // } else {
        //     throw new \Exception('Invalid database name');
        // }
        DB::unprepared("CREATE DATABASE IF NOT EXISTS $database_name");

        $this->databaseProxyManagerService->runForCompany(
            $company_id,
            function () {
                Artisan::call('migrate', [
                    '--database' => 'shard',
                    '--path' => '/database/migrations/micropowermanager',
                ]);
            }
        );

        return $company_database;
    }

    public function update($model, array $data): CompanyDatabase {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function getAll(?int $limit = null): Collection {
        throw new \Exception('Method getAll() not yet implemented.');
    }

    public function findByCompanyId(int $companyId): CompanyDatabase {
        /** @var CompanyDatabase $result */
        $result = $this->companyDatabase->newQuery()
            ->where(CompanyDatabase::COL_COMPANY_ID, '=', $companyId)
            ->firstOrFail();

        return $result;
    }
}
