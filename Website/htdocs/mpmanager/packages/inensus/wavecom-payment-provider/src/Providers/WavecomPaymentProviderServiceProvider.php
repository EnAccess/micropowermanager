<?php

namespace Inensus\WavecomPaymentProvider\Providers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Inensus\WavecomPaymentProvider\Console\Commands\InstallPackage;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;

class WavecomPaymentProviderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations();
            $this->commands([InstallPackage::class]);
        }

        Relation::morphMap(
            [
                WaveComTransaction::RELATION_NAME  => WaveComTransaction::class,
            ]
        );
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/wavecom-payment-provider.php', 'wavecom-payment-provider');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('WaveComPaymentProvider', WaveComTransactionProvider::class);
    }

    public function publishConfigFiles()
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/wavecom-payment-provider.php' => config_path(
                    'wavecom-payment-provider.php'
                ),
            ]
        );
    }

    public function publishVueFiles()
    {
        $this->publishes(
            [
                __DIR__ . '/../resources/assets' => resource_path(
                    'assets/js/plugins/wavecom-payment-provider'
                ),
            ],
            'vue-components'
        );
    }


    public function publishMigrations()
    {
        $this->publishes(
            [
                __DIR__ . '/../../database/migrations/create_wavecom_tables.php.stub'
                => $this->app->databasePath() . "/migrations/micropowermanager/" . Carbon::now()
                    . "_create_wavecom_tables.php",
            ],
            'migrations'
        );
    }
}
