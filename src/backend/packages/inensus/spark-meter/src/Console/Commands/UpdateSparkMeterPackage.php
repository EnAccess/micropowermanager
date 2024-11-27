<?php

namespace Inensus\SparkMeter\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\SparkMeter\Helpers\InsertSparkMeterApi;
use Inensus\SparkMeter\Services\CredentialService;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\MeterModelService;
use Inensus\SparkMeter\Services\PackageInstallationService;
use Inensus\SparkMeter\Services\SiteService;
use Inensus\SparkMeter\Services\SmSmsBodyService;
use Inensus\SparkMeter\Services\SmSmsFeedbackWordService;
use Inensus\SparkMeter\Services\SmSmsSettingService;
use Inensus\SparkMeter\Services\SmSmsVariableDefaultValueService;
use Inensus\SparkMeter\Services\SmSyncSettingService;

class UpdateSparkMeterPackage extends Command {
    protected $signature = 'spark-meter:update';
    protected $description = 'Update the Spark Meter Integration Package';

    public function __construct(
        private InsertSparkMeterApi $insertSparkMeterApi,
        private MeterModelService $meterModelService,
        private CredentialService $credentialService,
        private CustomerService $customerService,
        private SiteService $siteService,
        private SmSmsSettingService $smsSettingService,
        private SmSyncSettingService $syncSettingService,
        private SmSmsBodyService $smsBodyService,
        private SmSmsVariableDefaultValueService $defaultValueService,
        private SmSmsFeedbackWordService $smSmsFeedbackWordService,
        private PackageInstallationService $packageInstallationService,
        private Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void {
        $this->info('Spark Meter Integration Updating Started\n');
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
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  remove inensus/spark-meter');
    }

    private function installNewVersionOfPackage() {
        $this->info('Installing last version of package\n');
        echo shell_exec('COMPOSER_MEMORY_LIMIT=-1 ../composer.phar  require inensus/spark-meter');
    }

    private function deleteMigration(Filesystem $filesystem) {
        $migrationFile = $filesystem->glob(database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'*_create_spark_tables.php')[0];
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
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => 'migrations',
        ]);
    }

    private function updateDatabase() {
        $this->info('Updating database tables\n');
        $this->call('migrate');
    }

    private function publishVueFilesAgain() {
        $this->info('Updating vue files\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\SparkMeter\Providers\SparkMeterServiceProvider",
            '--tag' => 'vue-components',
            '--force' => true,
        ]);
    }
}
