<?php

namespace Inensus\WavecomPaymentProvider\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\WavecomPaymentProvider\Console\Commands\InstallPackage;
use Inensus\WavecomPaymentProvider\Models\WaveComTransaction;

class WavecomPaymentProviderServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                WaveComTransaction::RELATION_NAME => WaveComTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(WaveComTransactionProvider::class);
        $this->app->alias(WaveComTransactionProvider::class, 'WaveComPaymentProvider');
    }
}
