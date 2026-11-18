<?php

namespace App\Plugins\CalinMeter\Providers;

use App\Lib\IVendingPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\CalinMeter\CalinMeterApi;
use App\Plugins\CalinMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinMeter\Models\CalinTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinMeterServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::CALIN_METER;
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
        $this->app->bind(CalinMeterApi::class);
        $this->app->alias(CalinMeterApi::class, 'CalinMeterApi');
    }
}
