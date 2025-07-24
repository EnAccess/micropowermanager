<?php

namespace Inensus\WavecomPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\WavecomPaymentProvider\Console\Commands\InstallPackage;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;

class WavecomPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
        }
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                WaveComTransaction::RELATION_NAME => WaveComTransaction::class,
            ]
        );
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/wavecom-payment-provider.php', 'wavecom-payment-provider');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('WaveComPaymentProvider', WaveComTransactionProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes(
            [
                __DIR__.'/../../config/wavecom-payment-provider.php' => config_path(
                    'wavecom-payment-provider.php'
                ),
            ]
        );
    }

    public function publishVueFiles() {
        $this->publishes(
            [
                __DIR__.'/../resources/assets' => resource_path(
                    'assets/js/plugins/wavecom-payment-provider'
                ),
            ],
            'vue-components'
        );
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_wavecom_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_wavecom_tables.php'))) {
                    $file = $filesystem->glob($path.'*_wavecom_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.
                        '/../../database/migrations/create_wavecom_tables.php'));
                }

                return $filesystem->glob($path.'*_wavecom_tables.php');
            })->push($this->app->databasePath().
                "/migrations/{$timestamp}_create_wavecom_tables.php")
            ->first();
    }
}
