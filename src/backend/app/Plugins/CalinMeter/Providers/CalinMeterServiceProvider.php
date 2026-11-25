<?php

namespace App\Plugins\CalinMeter\Providers;

use App\Enums\MpmPlugin;
use App\Lib\IManufacturerPluginProvider;
use App\Plugins\CalinMeter\CalinMeterApi;
use App\Plugins\CalinMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinMeter\Models\CalinTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPlugin(): MpmPlugin {
        return MpmPlugin::CalinMeter;
    }

    public function tokenIssuer(): string {
        return CalinMeterApi::class;
    }

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
    }
}
