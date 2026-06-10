<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Providers;

use App\Plugins\PesapalPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCompanyHashService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalIpnService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTokenService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class PesapalPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([
            InstallPackage::class,
        ]);

        Relation::morphMap([
            PesapalTransaction::RELATION_NAME => PesapalTransaction::class,
        ]);

        $this->app->singleton(PesapalCredentialService::class);
        $this->app->singleton(PesapalTokenService::class);
        $this->app->singleton(PesapalIpnService::class);
        $this->app->singleton(PesapalTransactionService::class);
        $this->app->singleton(PesapalCompanyHashService::class);
        $this->app->singleton('PesapalPaymentProvider', PesapalTransactionProvider::class);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
