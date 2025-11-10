<?php

namespace Inensus\SteamaMeter\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\SteamaMeter\Console\Commands\InstallPackage;
use Inensus\SteamaMeter\Console\Commands\ReadHourlyMeterReadings;
use Inensus\SteamaMeter\Console\Commands\SteamaMeterDataSynchronizer;
use Inensus\SteamaMeter\Console\Commands\SteamaSmsNotifier;
use Inensus\SteamaMeter\Console\Commands\UpdatePackage;
use Inensus\SteamaMeter\Models\SteamaAssetRatesPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaCustomerBasisTimeOfUsage;
use Inensus\SteamaMeter\Models\SteamaFlatRatePaymentPlan;
use Inensus\SteamaMeter\Models\SteamaHybridPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaMinimumTopUpRequirementsPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaSmsSetting;
use Inensus\SteamaMeter\Models\SteamaSubscriptionPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaSyncSetting;
use Inensus\SteamaMeter\Models\SteamaTariffOverridePaymentPlan;
use Inensus\SteamaMeter\Models\SteamaTransaction;
use Inensus\SteamaMeter\SteamaMeterApi;

class SteamaMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
            SteamaMeterDataSynchronizer::class,
            SteamaSmsNotifier::class,
            UpdatePackage::class,
            ReadHourlyMeterReadings::class,
        ]);
        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('steama-meter:dataSync')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('steama-meter:smsNotifier')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
            $app->make(Schedule::class)->command('steama-meter:hourlyReadings')->hourlyAt(1)->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });
        Relation::morphMap(
            [
                'flat_rate' => SteamaFlatRatePaymentPlan::class,
                'hybrid' => SteamaHybridPaymentPlan::class,
                'subscription' => SteamaSubscriptionPaymentPlan::class,
                'minimum_top_up' => SteamaMinimumTopUpRequirementsPaymentPlan::class,
                'asset_rates' => SteamaAssetRatesPaymentPlan::class,
                'tariff_override' => SteamaTariffOverridePaymentPlan::class,
                'customer_time_of_usage' => SteamaCustomerBasisTimeOfUsage::class,
                'steama_transaction' => SteamaTransaction::class,
                'steama_sync_setting' => SteamaSyncSetting::class,
                'steama_sms_setting' => SteamaSmsSetting::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(SteamaMeterApi::class);
        $this->app->alias(SteamaMeterApi::class, 'SteamaMeterApi');
    }
}
