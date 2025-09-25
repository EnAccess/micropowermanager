<?php

namespace Inensus\SafaricomMobileMoney\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Inensus\SafaricomMobileMoney\Services\SafaricomMobileMoneyService;

class InstallPackage extends Command {
    protected $signature = 'safaricom-mobile-money:install';
    protected $description = 'Install SafaricomMobileMoney Package';

    public function __construct(
        private SafaricomMobileMoneyService $safaricomService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SafaricomMobileMoney Integration Package\n');

        $this->publishMigrations();
        $this->createDatabaseTables();
        $this->publishVueFiles();

        $this->safaricomService->initialize();
        $this->info('Package installed successfully..');
    }

    private function publishMigrations() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SafaricomMobileMoney\Providers\SafaricomMobileMoneyServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function createDatabaseTables() {
        $this->info('Creating database tables\n');
        $this->call('migrate-tenant');
    }

    private function publishVueFiles() {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SafaricomMobileMoney\Providers\SafaricomMobileMoneyServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }
}
