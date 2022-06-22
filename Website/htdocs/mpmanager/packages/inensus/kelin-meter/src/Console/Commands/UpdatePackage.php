<?php


namespace Inensus\KelinMeter\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\MenuItemService;
use Inensus\KelinMeter\Services\PackageInstallationService;


class UpdatePackage extends Command
{
    protected $signature = 'kelin-meter:update';
    protected $description = 'Update Kelin Meter Package';

    private $menuItemService;
    private $credentialService;
    private $apiHelpers;
    private $packageInstallationService;
    private $fileSystem;

    public function __construct(
        MenuItemService $menuItemService,
        KelinCredentialService $credentialService,
        ApiHelpers $apiHelpers,
        PackageInstallationService $packageInstallationService,
        Filesystem $fileSystem
    ) {
        parent::__construct();
        $this->apiHelpers = $apiHelpers;
        $this->menuItemService = $menuItemService;
        $this->credentialService = $credentialService;
        $this->packageInstallationService = $packageInstallationService;
        $this->fileSystem = $fileSystem;
    }

    public function handle(): void
    {
        $this->info('Kelin Meter Integration Updating Started\n');

        $this->removeOldVersionOfPackage();
        $this->installNewVersionOfPackage();
        $this->deleteMigration($this->fileSystem);
        $this->publishMigrationsAgain();
        $this->updateDatabase();
        $this->publishVueFilesAgain();
        $this->packageInstallationService->createDefaultSettingRecords();
        $this->call('routes:generate');
        $this->createMenuItems();
        $this->call('sidebar:generate');
        $this->info('Package updated successfully..');

    }

    private function removeOldVersionOfPackage()
    {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/kelin-meter');
    }

    private function installNewVersionOfPackage()
    {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/kelin-meter');
    }

    private function deleteMigration(Filesystem $filesystem)
    {
        $migrationFile = $filesystem->glob(database_path() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR . '*_create_kelin_tables.php')[0];
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
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
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
            '--provider' => "Inensus\KelinMeter\Providers\KelinMeterServiceProvider",
            '--tag' => "vue-components",
            '--force' => true,
        ]);
    }

    private function createMenuItems()
    {
        $menuItems = $this->menuItemService->createMenuItems();
        if (array_key_exists('menuItem', $menuItems)) {
            $this->call('menu-items:generate', [
                'menuItem' => $menuItems['menuItem'],
                'subMenuItems' => $menuItems['subMenuItems'],
            ]);
        }
    }
}