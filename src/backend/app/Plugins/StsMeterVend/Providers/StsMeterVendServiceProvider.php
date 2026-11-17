<?php

namespace App\Plugins\StsMeterVend\Providers;

use App\Lib\IVendingPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\StsMeterVend\Console\Commands\InstallPackage;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class StsMeterVendServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::STS_METER_VEND;
    }

    public function tokenIssuer(): string {
        return StsMeterApi::class;
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(['sts_transaction' => StsTransaction::class]);
    }

    public function register(): void {
        $this->app->register(ObserverServiceProvider::class);
    }
}
