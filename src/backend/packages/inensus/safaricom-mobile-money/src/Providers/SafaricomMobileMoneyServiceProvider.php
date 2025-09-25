<?php

namespace Inensus\SafaricomMobileMoney\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\SafaricomMobileMoney\Console\Commands\InstallPackage;
use Inensus\SafaricomMobileMoney\Models\SafaricomTransaction;
use Inensus\SafaricomMobileMoney\Providers\EventServiceProvider;
use Inensus\SafaricomMobileMoney\Providers\ObserverServiceProvider;
use Inensus\SafaricomMobileMoney\Providers\RouteServiceProvider;

class SafaricomMobileMoneyServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }

        Relation::morphMap(
            [
                SafaricomTransaction::RELATION_NAME => SafaricomTransaction::class,
            ]
        );
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/safaricom-mobile-money.php', 'safaricom-mobile-money');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(SafaricomMobileMoneyTransactionProvider::class);
        $this->app->alias(SafaricomMobileMoneyTransactionProvider::class, 'SafaricomMobileMoneyTransactionProvider');
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/safaricom-mobile-money.php' => config_path('safaricom-mobile-money.php'),
        ], 'config');
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/safaricom-mobile-money'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_safaricom_mobile_money_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_safaricom_mobile_money_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_safaricom_mobile_money_tables.php')[0];
                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/create_safaricom_mobile_money_tables.php.stub'));
                }

                return $filesystem->glob($path.'*_create_safaricom_mobile_money_tables.php');
            })->push($this->app->databasePath()."/migrations/tenant/{$timestamp}_create_safaricom_mobile_money_tables.php")
            ->first();
    }
}
