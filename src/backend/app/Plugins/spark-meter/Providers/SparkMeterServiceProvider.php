<?php

namespace Inensus\SparkMeter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\SparkMeter\Console\Commands\InstallSparkMeterPackage;
use Inensus\SparkMeter\Console\Commands\SparkMeterDataSynchronizer;
use Inensus\SparkMeter\Console\Commands\SparkMeterSmsNotifier;
use Inensus\SparkMeter\Console\Commands\SparkMeterTransactionStatusCheck;
use Inensus\SparkMeter\Console\Commands\UpdateSparkMeterPackage;
use Inensus\SparkMeter\Models\SmSmsSetting;
use Inensus\SparkMeter\Models\SmSyncSetting;
use Inensus\SparkMeter\Models\SmTransaction;
use Inensus\SparkMeter\SparkMeterApi;

class SparkMeterServiceProvider extends ServiceProvider {
    /**
     * Bootstrap the application services.
     */
    public function boot(Filesystem $filesystem): void {
        $this->app->register(SparkMeterRouteServiceProvider::class);
        $this->commands([
            InstallSparkMeterPackage::class,
            SparkMeterTransactionStatusCheck::class,
            SparkMeterDataSynchronizer::class,
            SparkMeterSmsNotifier::class,
            UpdateSparkMeterPackage::class,
        ]);
        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('spark-meter:dataSync')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('spark-meter:smsNotifier')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });

        Relation::morphMap(
            [
                'spark_transaction' => SmTransaction::class,
                'spark_sync_setting' => SmSyncSetting::class,
                'spark_sms_setting' => SmSmsSetting::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(SparkMeterApi::class);
        $this->app->alias(SparkMeterApi::class, 'SparkMeterApi');
    }
}
