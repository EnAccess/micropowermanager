<?php

namespace App\Plugins\SteamaMeter\Providers;

use App\Plugins\SteamaMeter\Console\Commands\InstallPackage;
use App\Plugins\SteamaMeter\Console\Commands\ReadHourlyMeterReadings;
use App\Plugins\SteamaMeter\Console\Commands\SteamaMeterDataSynchronizer;
use App\Plugins\SteamaMeter\Console\Commands\SteamaSmsNotifier;
use App\Plugins\SteamaMeter\Console\Commands\UpdatePackage;
use App\Plugins\SteamaMeter\Models\SteamaAssetRatesPaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaCustomerBasisTimeOfUsage;
use App\Plugins\SteamaMeter\Models\SteamaFlatRatePaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaHybridPaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaMinimumTopUpRequirementsPaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaSmsSetting;
use App\Plugins\SteamaMeter\Models\SteamaSubscriptionPaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use App\Plugins\SteamaMeter\Models\SteamaTariffOverridePaymentPlan;
use App\Plugins\SteamaMeter\Models\SteamaTransaction;
use App\Plugins\SteamaMeter\SteamaMeterApi;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
