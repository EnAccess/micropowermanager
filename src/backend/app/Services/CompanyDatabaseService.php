<?php

namespace App\Services;

use App\Models\CompanyDatabase;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * @implements IBaseService<CompanyDatabase>
 */
class CompanyDatabaseService implements IBaseService {
    /** @use HasCrudOperations<CompanyDatabase> */
    use HasCrudOperations;

    public function __construct(
        private CompanyDatabase $companyDatabase,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {}

    protected function crudModel(): CompanyDatabase {
        return $this->companyDatabase;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): CompanyDatabase {
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
                    '--database' => 'tenant',
                    '--path' => '/database/migrations/tenant',
                    '--force' => true,
                ]);
            }
        );

        return $company_database;
    }

    /**
     * Creates only the CompanyDatabase record without setting up the physical database.
     * Use this when you need more control over the database creation process (e.g., within transactions).
     *
     * @param array<string, mixed> $data
     */
    public function createRecord(array $data): CompanyDatabase {
        return $this->companyDatabase->newQuery()->create($data);
    }

    public function findByCompanyId(int $companyId): CompanyDatabase {
        return $this->companyDatabase->newQuery()
            ->where(CompanyDatabase::COL_COMPANY_ID, '=', $companyId)
            ->firstOrFail();
    }
}
