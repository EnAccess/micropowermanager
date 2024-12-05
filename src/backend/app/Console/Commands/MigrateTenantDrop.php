<?php

namespace App\Console\Commands;

use App\Utils\DemoCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTenantDrop extends Command {
    protected $signature = 'migrate-tenant:drop-demo-company';
    protected $description = 'Drop all tables on the Demo tenant database';

    public function handle() {
        $this->call('optimize:clear');
        $database = DemoCompany::DEMO_COMPANY_DATABASE_NAME;
        DB::statement("DROP DATABASE IF EXISTS `$database`");
    }
}
