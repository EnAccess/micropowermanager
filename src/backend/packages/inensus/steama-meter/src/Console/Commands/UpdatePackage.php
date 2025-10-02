<?php

namespace Inensus\SteamaMeter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider;
use Inensus\SteamaMeter\Services\PackageInstallationService;

class UpdatePackage extends Command {
    protected $signature = 'steama-meter:update';
    protected $description = 'Install Steamaco Meter Package';

    public function __construct(
        private PackageInstallationService $packageInstallationService,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Steamaco Meter Integration Updating Started\n');

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
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/steama-meter');
    }

    private function installNewVersionOfPackage(): void {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/steama-meter');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_steama_tables.php')[0];
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
            '--provider' => SteamaMeterServiceProvider::class,
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
            '--provider' => SteamaMeterServiceProvider::class,
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }
}
