<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\PaystackPaymentProvider\Console\Commands\InstallPackage;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;
use Inensus\PaystackPaymentProvider\Services\PaystackWebhookService;

class PaystackPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([
                InstallPackage::class,
            ]);
        } else {
            $this->commands([
                InstallPackage::class,
            ]);
        }

        // Register morph map for PaystackTransaction
        Relation::morphMap([
            PaystackTransaction::RELATION_NAME => PaystackTransaction::class,
        ]);

        // Register services
        $this->app->singleton(PaystackCredentialService::class);
        $this->app->singleton(PaystackWebhookService::class);
        $this->app->singleton(PaystackTransactionService::class);
        $this->app->singleton('PaystackPaymentProvider', PaystackTransactionProvider::class);
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/paystack-payment-provider.php', 'paystack-payment-provider');
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/paystack-payment-provider.php' => config_path('paystack-payment-provider.php'),
        ], 'paystack-payment-provider-config');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_paystack_payment_provider_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_paystack_payment_provider_tables.php'),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem, string $migrationName): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationName) {
                if (count($filesystem->glob($path.'*_'.$migrationName))) {
                    $file = $filesystem->glob($path.'*_'.$migrationName)[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/'.$migrationName.'.stub')
                    );
                }

                return $filesystem->glob($path.'*_'.$migrationName);
            })->push($this->app->databasePath()."/migrations/tenant/{$timestamp}_{$migrationName}")
            ->first();
    }
}
