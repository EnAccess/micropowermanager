<?php

namespace Inensus\MesombPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\MesombPaymentProvider\Console\Commands\InstallPackage;
use Inensus\MesombPaymentProvider\Console\Commands\UpdatePackage;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;

class MesombServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class, UpdatePackage::class]);
        }
        Relation::morphMap(
            [
                'mesomb_transaction' => MesombTransaction::class,
            ]
        );
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/mesomb-payment-provider.php', 'mesomb-payment-provider');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton('MesombPaymentProvider', MesombTransactionProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/mesomb-payment-provider.php' => config_path('mesomb-payment-provider.php'),
        ]);
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_mesomb_payment_provider_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_mesomb_payment_provider_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_mesomb_payment_provider_tables.php')[0];

                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_mesomb_payment_provider_tables.php.stub')
                    );
                }

                return $filesystem->glob($path.'*_create_mesomb_payment_provider_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_mesomb_payment_provider_tables.php")
            ->first();
    }
}
