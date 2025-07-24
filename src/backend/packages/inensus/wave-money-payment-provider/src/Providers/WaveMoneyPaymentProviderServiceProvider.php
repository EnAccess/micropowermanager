<?php

namespace Inensus\WaveMoneyPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\WaveMoneyPaymentProvider\Console\Commands\InstallPackage;
use Inensus\WaveMoneyPaymentProvider\Console\Commands\UpdatePackage;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;

class WaveMoneyPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class, UpdatePackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
        Relation::morphMap(
            [
                WaveMoneyTransaction::RELATION_NAME => WaveMoneyTransaction::class,
            ]
        );
    }

    public function register() {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/wave-money-payment-provider.php',
            'wave-money-payment-provider'
        );
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton('WaveMoneyPaymentProvider', WaveMoneyTransactionProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.
            '/../../config/wave-money-payment-provider-integration.php' => config_path('wave-money-payment-provider.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/wave-money-payment-provider'
            ),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_viber_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_wave_money_payment_provider_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_wave_money_payment_provider_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.
                        '/../../database/migrations/create_wave_money_payment_provider_tables.php'));
                }

                return $filesystem->glob($path.'*_create_wave_money_payment_provider_tables.php');
            })->push($this->app->databasePath().
                "/migrations/{$timestamp}_create_wave_money_payment_provider_tables.php")
            ->first();
    }
}
