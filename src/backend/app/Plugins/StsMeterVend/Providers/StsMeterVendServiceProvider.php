<?php

namespace App\Plugins\StsMeterVend\Providers;

use App\Lib\VendingProvider;
use App\Lib\VendingProviderRegistry;
use App\Models\MpmPlugin;
use App\Plugins\StsMeterVend\Console\Commands\InstallPackage;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use App\Plugins\StsMeterVend\Services\ManufacturerService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class StsMeterVendServiceProvider extends ServiceProvider {
    public function boot(VendingProviderRegistry $vendingProviders): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(['sts_transaction' => StsTransaction::class]);

        $vendingProviders->register(new VendingProvider(
            key: StsMeterApi::VENDING_PROVIDER,
            name: 'Native STS (nxt-sts)',
            apiClass: StsMeterApi::class,
            mpmPluginId: MpmPlugin::STS_METER_VEND,
            manufacturerApiNames: array_keys(ManufacturerService::SUPPORTED_MANUFACTURERS),
        ));
    }

    public function register(): void {
        $this->app->register(ObserverServiceProvider::class);
    }
}
