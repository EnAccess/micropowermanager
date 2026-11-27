<?php

namespace App\Plugins\CalinMeterV2\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\CalinMeterV2\CalinMeterV2Api;
use App\Plugins\CalinMeterV2\Console\Commands\InstallPackage;
use App\Plugins\CalinMeterV2\Models\CalinV2Transaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class CalinMeterV2ServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'calin_meter_v2';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Calin Meter API V2';
    }

    public function description(): string {
        return 'Use this if Calin set up your own AMR system with its own address, often for LoRaWAN or gateway-connected meters, and gave you a user ID and password. Vends kWh tokens and clears credit. If you log in at ami.calinhost.com with a company name and vending password, use Calin Smart Meter.';
    }

    public function usageType(): string {
        return 'mini-grid';
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
