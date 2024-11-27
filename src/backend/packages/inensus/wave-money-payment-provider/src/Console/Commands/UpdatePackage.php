<?php

namespace Inensus\WaveMoneyPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;

class UpdatePackage extends Command {
    protected $signature = 'wave-money-payment-provider:update';
    protected $description = 'Update WaveMoney Package';

    public function __construct(
        private WaveMoneyCredentialService $credentialService,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('WaveMoney Integration Updating Started\n');
        // $this->removeOldVersionOfPackage();
        // $this->installNewVersionOfPackage();
        // $this->deleteMigration($this->filesystem);
        // $this->publishMigrationsAgain();
        // $this->updateDatabase();
        // $this->publishVueFilesAgain();
        // $this->call('routes:generate');
        // $this->call('sidebar:generate');
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage() {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/wave-money-payment-provider');
    }

    private function installNewVersionOfPackage() {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/wave-money-payment-provider');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.
            '*_create_wave_money_payment_provider_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return false;
        }

        return DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->delete();
    }

    private function publishMigrationsAgain() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase() {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain() {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }
}
