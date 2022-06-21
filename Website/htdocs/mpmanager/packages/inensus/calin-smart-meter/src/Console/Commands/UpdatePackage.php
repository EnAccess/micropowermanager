<?php


namespace Inensus\CalinSmartMeter\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Inensus\CalinSmartMeter\Services\CalinSmartCredentialService;
use Inensus\CalinSmartMeter\Services\MenuItemService;

class UpdatePackage extends Command
{
    protected $signature = 'calin-smart-meter:update';
    protected $description = 'Update CalinSmartMeter Package';

    private $menuItemService;
    private $credentialService;
    private $fileSystem;

    public function __construct(
        MenuItemService $menuItemService,
        CalinSmartCredentialService $credentialService,
        Filesystem $fileSystem
    ) {
        parent::__construct();
        $this->menuItemService = $menuItemService;
        $this->credentialService = $credentialService;
        $this->fileSystem = $fileSystem;
    }

    public function handle(): void
    {
        $this->info('Calin Smart Meter Integration Updating Started\n');
        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->fileSystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->call('sidebar:generate');
        $this->info('Package updated successfully..');
    }

    private function removeOldVersionOfPackage()
    {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/calin-smart-meter');
    }

    private function installNewVersionOfPackage()
    {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/calin-smart-meter');
    }

    private function deleteMigration(Filesystem $filesystem)
    {
        $migrationFile = $filesystem->glob(database_path() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*_create_calin_smart_tables.php')[0];
        $migration = DB::table('migrations')
            ->where('migration', substr(explode("/migrations/", $migrationFile)[1], 0, -4))->first();
        if (!$migration) {
            return false;
        }
        return DB::table('migrations')
            ->where('migration', substr(explode("/migrations/", $migrationFile)[1], 0, -4))->delete();

    }

    private function publishMigrationsAgain()
    {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
            '--tag' => "migrations"
        ]);
    }

    private function updateDatabase()
    {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain()
    {
        $this->info('Copying vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider",
            '--tag' => "vue-components"
        ]);
    }

    private function createMenuItems()
    {
        $menuItems = $this->menuItemService->createMenuItems();
        $this->call('menu-items:generate', [
            'menuItem' => $menuItems['menuItem'],
            'subMenuItems' => $menuItems['subMenuItems'],
        ]);
    }
}