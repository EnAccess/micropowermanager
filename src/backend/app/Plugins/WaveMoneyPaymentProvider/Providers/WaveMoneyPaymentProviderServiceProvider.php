<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Providers;

use App\Plugins\WaveMoneyPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class WaveMoneyPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                WaveMoneyTransaction::RELATION_NAME => WaveMoneyTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(WaveMoneyTransactionProvider::class);
        $this->app->alias(WaveMoneyTransactionProvider::class, 'WaveMoneyPaymentProvider');
    }
}
