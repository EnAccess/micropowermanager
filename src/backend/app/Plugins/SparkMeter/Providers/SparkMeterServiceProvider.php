<?php

namespace App\Plugins\SparkMeter\Providers;

use App\Plugins\SparkMeter\Console\Commands\InstallSparkMeterPackage;
use App\Plugins\SparkMeter\Console\Commands\SparkMeterDataSynchronizer;
use App\Plugins\SparkMeter\Console\Commands\SparkMeterSmsNotifier;
use App\Plugins\SparkMeter\Console\Commands\SparkMeterTransactionStatusCheck;
use App\Plugins\SparkMeter\Console\Commands\UpdateSparkMeterPackage;
use App\Plugins\SparkMeter\Models\SmSmsSetting;
use App\Plugins\SparkMeter\Models\SmSyncSetting;
use App\Plugins\SparkMeter\Models\SmTransaction;
use App\Plugins\SparkMeter\SparkMeterApi;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
