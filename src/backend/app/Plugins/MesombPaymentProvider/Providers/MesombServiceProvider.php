<?php

namespace App\Plugins\MesombPaymentProvider\Providers;

use App\Plugins\MesombPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

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
