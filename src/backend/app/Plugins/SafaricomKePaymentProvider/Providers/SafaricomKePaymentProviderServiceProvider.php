<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Providers;

use App\Plugins\SafaricomKePaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomAuthService;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomCredentialService;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SafaricomKePaymentProviderServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);

        Relation::morphMap([
            SafaricomTransaction::RELATION_NAME => SafaricomTransaction::class,
        ]);

        $this->app->singleton(SafaricomCredentialService::class);
        $this->app->singleton(SafaricomAuthService::class);
        $this->app->singleton(SafaricomTransactionService::class);
        $this->app->singleton('SafaricomKePaymentProvider', SafaricomKeTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(SafaricomKeTransactionProvider::class);
        $this->app->alias(SafaricomKeTransactionProvider::class, 'SafaricomKeTransactionProvider');
    }
}
