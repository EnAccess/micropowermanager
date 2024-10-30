<?php

namespace App\Console\Commands;

use App\Services\CompanyDatabaseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class CreateDummyCompanyWithData extends Command
{
    public const SQL_DUMMY_DATA_FILE_NAMES = [
        'dummy_agent_data.sql',
        'dummy_data.sql',
        'dummy_plugin_data.sql',
        'dummy_sms_data.sql',
        'dummy_ticket_data.sql',
        'dummy_transaction_data.sql',
    ];
    public const DUMMY_COMPANY_DATA = [
        'name' => 'Dummy Company',
        'address' => 'Dummy Address',
        'phone' => '+255123456789',
        'country_id' => -1,
        'email' => 'dummy@company.com',
        'protected_page_password' => '123123',
    ];
    public const DUMMY_COMPANY_USER = [
        'password' => '123123',
        'email' => 'dummy@user.com',
        'name' => 'Dummy User',
    ];
    public const DUMMY_DATABASE_NAME = 'DummyCompany_1';

    protected $signature = 'dummy:create-company-with-dummy-data';
    protected $description = 'Create a dummy company with dummy data for development environment';

    public function __construct(
        private CompanyDatabaseService $companyDatabaseService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {
        parent::__construct();
    }

    public function handle()
    {
        try {
            foreach (self::SQL_DUMMY_DATA_FILE_NAMES as $sqlFile) {
                $path = __DIR__.'/../../../database/dummyData/'.$sqlFile;

                $this->databaseProxyManagerService->runForCompany(
                    // $company->getId(),
                    1,
                    fn () => $this->importSqlDump($path, self::DUMMY_DATABASE_NAME)
                );
            }

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
            $message = 'Error while importing sql dump. '.$e->getMessage();
            throw new \Exception(mb_substr($message, 0, 1000));
        }
    }
}
