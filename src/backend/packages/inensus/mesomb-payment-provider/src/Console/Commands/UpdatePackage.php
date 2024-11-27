<?php

namespace Inensus\MesombPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class UpdatePackage extends Command {
    protected $signature = 'mesomb-payment-provider:update';
    protected $description = 'Update the Mesomb Payment Provider Integration Package';

    public function __construct(
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void {
        $this->info('Mesomb Payment Provider Integration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->filesystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage() {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/mesomb-payment-provider');
    }

    private function installNewVersionOfPackage() {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/mesomb-payment-provider');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_mesomb_payment_provider_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return;
        }
        DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->delete();
    }

    private function publishMigrationsAgain() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\MesombPaymentProvider\Providers\MesombServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase() {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }
}
