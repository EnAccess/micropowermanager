<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Providers;

use App\Plugins\FlutterwavePaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCompanyHashService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCredentialService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveWebhookService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class FlutterwavePaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
        ]);

        // Register morph map for FlutterwaveTransaction
        Relation::morphMap([
            FlutterwaveTransaction::RELATION_NAME => FlutterwaveTransaction::class,
        ]);

        // Register services
        $this->app->singleton(FlutterwaveCredentialService::class);
        $this->app->singleton(FlutterwaveWebhookService::class);
        $this->app->singleton(FlutterwaveTransactionService::class);
        $this->app->singleton(FlutterwaveCompanyHashService::class);
        $this->app->singleton('FlutterwavePaymentProvider', FlutterwaveTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
