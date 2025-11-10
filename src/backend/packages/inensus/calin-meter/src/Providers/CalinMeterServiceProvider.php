<?php

namespace Inensus\CalinMeter\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Inensus\CalinMeter\CalinMeterApi;
use Inensus\CalinMeter\Console\Commands\InstallPackage;
use Inensus\CalinMeter\Models\CalinTransaction;

class CalinMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                'calin_transaction' => CalinTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(CalinMeterApi::class);
        $this->app->alias(CalinMeterApi::class, 'CalinMeterApi');
    }
}
