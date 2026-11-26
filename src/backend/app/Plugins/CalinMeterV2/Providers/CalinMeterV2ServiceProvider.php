<?php

namespace App\Plugins\CalinMeterV2\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Models\MpmPlugin;
use App\Plugins\CalinMeterV2\CalinMeterV2Api;
use App\Plugins\CalinMeterV2\Console\Commands\InstallPackage;
use App\Plugins\CalinMeterV2\Models\CalinV2Transaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CalinMeterV2ServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public function mpmPluginId(): int {
        return MpmPlugin::CALIN_METER_V2;
    }

    public function tokenIssuer(): string {
        return CalinMeterV2Api::class;
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap([
            'calin_v2_transaction' => CalinV2Transaction::class,
        ]);
    }
}
