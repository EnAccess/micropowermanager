<?php

namespace Inensus\SteamaMeter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Services\PackageInstallationService;
use Inensus\SteamaMeter\Services\SteamaAgentService;
use Inensus\SteamaMeter\Services\SteamaCredentialService;
use Inensus\SteamaMeter\Services\SteamaSiteLevelPaymentPlanTypeService;
use Inensus\SteamaMeter\Services\SteamaSiteService;
use Inensus\SteamaMeter\Services\SteamaSmsBodyService;
use Inensus\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use Inensus\SteamaMeter\Services\SteamaSmsSettingService;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;
use Inensus\SteamaMeter\Services\SteamaTariffService;
use Inensus\SteamaMeter\Services\SteamaUserTypeService;

class UpdatePackage extends Command {
    protected $signature = 'steama-meter:update';
    protected $description = 'Install Steamaco Meter Package';

    public function __construct(
        private SteamaAgentService $agentService,
        private SteamaCredentialService $credentialService,
        private SteamaSiteLevelPaymentPlanTypeService $paymentPlanService,
        private SteamaTariffService $tariffService,
        private SteamaUserTypeService $userTypeService,
        private ApiHelpers $apiHelpers,
        private SteamaSiteService $siteService,
        private SteamaSmsSettingService $smsSettingService,
        private SteamaSyncSettingService $syncSettingService,
        private SteamaSmsBodyService $smsBodyService,
        private SteamaSmsVariableDefaultValueService $defaultValueService,
        private SteamaSmsFeedbackWordService $steamaSmsFeedbackWordService,
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

    private function removeOldVersionOfPackage() {
        $this->info('Removing former version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/steama-meter');
    }

    private function installNewVersionOfPackage() {
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

    private function publishMigrationsAgain() {
        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider",
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
            '--provider' => "Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider",
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }
}
