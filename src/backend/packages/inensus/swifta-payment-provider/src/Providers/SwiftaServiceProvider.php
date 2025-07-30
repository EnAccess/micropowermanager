<?php

namespace Inensus\SwiftaPaymentProvider\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\SwiftaPaymentProvider\Console\Commands\InstallPackage;
use Inensus\SwiftaPaymentProvider\Console\Commands\TransactionStatusChecker;
use Inensus\SwiftaPaymentProvider\Console\Commands\UpdatePackage;
use Inensus\SwiftaPaymentProvider\Models\SwiftaTransaction;

class SwiftaServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class, UpdatePackage::class, TransactionStatusChecker::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
        Relation::morphMap(
            [
                SwiftaTransaction::RELATION_NAME => SwiftaTransaction::class,
            ]
        );
        $this->app->make(Schedule::class)->command('swifta-payment-provider:transactionStatusCheck')->dailyAt('00:00')
            ->appendOutputTo(storage_path('logs/cron.log'));
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/swifta-payment-provider.php', 'swifta-payment-provider');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('SwiftaPaymentProvider', SwiftaTransactionProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/swifta-payment-provider.php' => config_path('swifta-payment-provider.php'),
        ], 'configurations');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_swifta_payment_provider_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_swifta_payment_provider_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_swifta_payment_provider_tables.php')[0];

                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/create_swifta_payment_provider_tables.php.stub')
                    );
                }

                return $filesystem->glob($path.'*_create_swifta_payment_provider_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_swifta_payment_provider_tables.php")
            ->first();
    }
}
