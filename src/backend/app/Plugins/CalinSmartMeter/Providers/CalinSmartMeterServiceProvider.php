<?php

namespace App\Plugins\CalinSmartMeter\Providers;

use App\Plugins\CalinSmartMeter\CalinSmartMeterApi;
use App\Plugins\CalinSmartMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinSmartMeter\Models\CalinSmartTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinSmartMeterServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                'calin_smart_transaction' => CalinSmartTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(CalinSmartMeterApi::class);
        $this->app->alias(CalinSmartMeterApi::class, 'CalinSmartMeterApi');
    }
}
