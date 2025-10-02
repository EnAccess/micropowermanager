<?php

namespace Inensus\BulkRegistration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider;

class UpdatePackage extends Command {
    protected $signature = 'bulk-registration:update';
    protected $description = 'Update  Bulk Registration Package';

    public function __construct(
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Bulk Registration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->filesystem);
        $this->publishConfigurations();
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->call('routes:generate');
        $this->call('sidebar:generate');
        $this->info('Package updated successfully..');
    }

    private function publishConfigurations(): void {
        $this->info('Copying configurations\n');
        $this->call('vendor:publish', [
            '--provider' => BulkRegistrationServiceProvider::class,
            '--tag' => 'configurations',
        ]);
    }

    private function removeOldVersionOfPackage(): void {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/bulk-registration');
    }

    private function installNewVersionOfPackage(): void {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/bulk-registration');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_bulk-registration_tables.php')[0];
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
            '--provider' => BulkRegistrationServiceProvider::class,
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
            '--provider' => BulkRegistrationServiceProvider::class,
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }
}
