<?php


namespace Inensus\ViberMessaging\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use Inensus\ViberMessaging\Services\MenuItemService;
use Inensus\ViberMessaging\Services\ViberCredentialService;

class UpdatePackage extends Command
{
    protected $signature = 'viber-messaging:update';
    protected $description = 'Update ViberMessaging Package';

    private $fileSystem;

    public function __construct(
        private MenuItemService $menuItemService,
        private ViberCredentialService $credentialService,
        Filesystem $fileSystem
    ) {
        parent::__construct();

        $this->fileSystem = $fileSystem;
    }

    public function handle(): void
    {
        $this->info('Viber Messaging Integration Updating Started\n');
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
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/viber-messaging');
    }

    private function installNewVersionOfPackage()
    {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/viber-messaging');
    }

    private function deleteMigration(Filesystem $filesystem)
    {
        $migrationFile = $filesystem->glob(database_path() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR .
            '*_create_viber_tables.php')[0];
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
            '--provider' => "Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider",
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
            '--provider' => "Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider",
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
