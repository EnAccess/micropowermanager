<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyDatabase;
use App\Models\DatabaseProxy;
use App\Services\CompanyDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CreateDummyCompanyWithData extends Command
{
    const SQL_DUMMY_DATA_FILE_NAME = 'dummy_data.sql';
    const DUMMY_COMPANY_DATA = [
        'name' => 'Dummy Company',
        'address' => 'Dummy Address',
        'phone' => '+255123456789',
        'country_id' => -1,
        'email' => 'dummy@company.com',
        'protected_page_password' => '123123'
    ];
    const DUMMY_COMPANY_USER = [
        'password' => '123123',
        'email' => 'dummy@user.com',
        'name' => 'Dummy User',
    ];
    const DUMMY_DATABASE_NAME = 'DummyCompany_1';

    protected $signature = 'dummy:create-company-with-dummy-data';
    protected $description = 'Create a dummy company with dummy data for development environment';

    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyManagerService $databaseProxyManagerService
    ) {

        parent::__construct();
    }

    public function handle()
    {
        try {
            $path = __DIR__ . '/../../../database/dummyData/' . self::SQL_DUMMY_DATA_FILE_NAME;
            $creatorShellPath = __DIR__ . '/../../..';
            if (!file_exists($path)) {
                $message = "The specified SQL dump file does not exist.";
                throw new \Exception($message);
            }

            $databaseName = self::DUMMY_DATABASE_NAME;
            $company = Company::query()->firstOrCreate(self::DUMMY_COMPANY_DATA, self::DUMMY_COMPANY_DATA);
            $adminData = self::DUMMY_COMPANY_USER;

            $companyDatabaseData = [
                'company_id' => $company->getId(),
                'database_name' => $databaseName
            ];

            $companyDatabase = CompanyDatabase::query()->firstOrCreate($companyDatabaseData, $companyDatabaseData);
            DB::unprepared("DROP DATABASE IF EXISTS $databaseName");
            $this->companyDatabaseService->createNewDatabaseForCompany($databaseName, $company->getId());
            $databaseProxyData = [
                'email' => $adminData['email'],
                'fk_company_id' => $company->getId(),
                'fk_company_database_id' => $companyDatabase->getId()
            ];

            DatabaseProxy::query()->firstOrCreate($databaseProxyData, $databaseProxyData);
            $this->databaseProxyManagerService->runForCompany($company->getId(),
                fn() => $this->importSqlDump($path, $databaseName));

            return 0;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return 0;
        }
    }

    private function importSqlDump($path, $databaseName)
    {
        try {
            DB::select("USE $databaseName");
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        } catch (\Exception $e) {
            $message = "Error while importing sql dump. " . $e->getMessage();
            throw new \Exception(mb_substr($message, 0, 1000));
        }
    }
}
