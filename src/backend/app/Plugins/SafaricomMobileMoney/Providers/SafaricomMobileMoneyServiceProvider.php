<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomMobileMoney\Providers;

use App\Plugins\SafaricomMobileMoney\Console\Commands\InstallPackage;
use App\Plugins\SafaricomMobileMoney\Models\SafaricomTransaction;
use App\Plugins\SafaricomMobileMoney\Services\SafaricomAuthService;
use App\Plugins\SafaricomMobileMoney\Services\SafaricomCredentialService;
use App\Plugins\SafaricomMobileMoney\Services\SafaricomTransactionService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class SafaricomMobileMoneyServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);

        Relation::morphMap([
            SafaricomTransaction::RELATION_NAME => SafaricomTransaction::class,
        ]);

        $this->app->singleton(SafaricomCredentialService::class);
        $this->app->singleton(SafaricomAuthService::class);
        $this->app->singleton(SafaricomTransactionService::class);
        $this->app->singleton('SafaricomMobileMoney', SafaricomMobileMoneyTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(SafaricomMobileMoneyTransactionProvider::class);
        $this->app->alias(SafaricomMobileMoneyTransactionProvider::class, 'SafaricomMobileMoneyTransactionProvider');
    }
}
