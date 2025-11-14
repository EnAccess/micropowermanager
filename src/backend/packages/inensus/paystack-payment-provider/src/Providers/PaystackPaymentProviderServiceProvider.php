<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\PaystackPaymentProvider\Console\Commands\InstallPackage;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;
use Inensus\PaystackPaymentProvider\Services\PaystackWebhookService;

class PaystackPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
        ]);

        // Register morph map for PaystackTransaction
        Relation::morphMap([
            PaystackTransaction::RELATION_NAME => PaystackTransaction::class,
        ]);

        // Register services
        $this->app->singleton(PaystackCredentialService::class);
        $this->app->singleton(PaystackWebhookService::class);
        $this->app->singleton(PaystackTransactionService::class);
        $this->app->singleton(PaystackCompanyHashService::class);
        $this->app->singleton('PaystackPaymentProvider', PaystackTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
