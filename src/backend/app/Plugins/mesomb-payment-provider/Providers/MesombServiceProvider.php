<?php

namespace Inensus\MesombPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\MesombPaymentProvider\Console\Commands\InstallPackage;
use Inensus\MesombPaymentProvider\Models\MesombTransaction;

class MesombServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                'mesomb_transaction' => MesombTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(MesombTransactionProvider::class);
        $this->app->alias(MesombTransactionProvider::class, 'MesombPaymentProvider');
    }
}
