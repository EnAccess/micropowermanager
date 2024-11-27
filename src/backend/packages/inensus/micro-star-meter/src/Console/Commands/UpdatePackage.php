<?php

namespace Inensus\MicroStarMeter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

class UpdatePackage extends Command {
    protected $signature = 'micro-star-meter:update';
    protected $description = 'Update MicroStarMeter Package';

    public function __construct(
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('MicroStar Meter Integration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->filesystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->call('routes:generate');
        $this->call('sidebar:generate');
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage() {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/micro-star-meter');
    }

    private function installNewVersionOfPackage() {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/micro-star-meter');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR
            .'*_create_micro_star_tables.php')[0];
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
            '--provider' => "Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider",
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
            '--provider' => "Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider",
            '--tag' => 'vue-components',
        ]);
    }
}
