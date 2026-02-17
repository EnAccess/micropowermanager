<?php

namespace App\Plugins\KelinMeter\Console\Commands;

use App\Plugins\KelinMeter\Providers\KelinMeterServiceProvider;
use App\Plugins\KelinMeter\Services\PackageInstallationService;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class UpdatePackage extends Command {
    protected $signature = 'kelin-meter:update';
    protected $description = 'Update Kelin Meter Package';

    public function __construct(
        private PackageInstallationService $packageInstallationService,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Kelin Meter Integration Updating Started\n');

        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->filesystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->call('routes:generate');
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage(): void {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/kelin-meter');
    }

    private function installNewVersionOfPackage(): void {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/kelin-meter');
    }

    private function deleteMigration(Filesystem $filesystem): mixed {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_kelin_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return false;
        }

        return DB::table('migrations')
            ->where('migration', substr(explode('/migrations/', $migrationFile)[1], 0, -4))->delete();
    }

    private function publishMigrationsAgain(): void {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => KelinMeterServiceProvider::class,
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase(): void {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain(): void {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => KelinMeterServiceProvider::class,
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }
}
