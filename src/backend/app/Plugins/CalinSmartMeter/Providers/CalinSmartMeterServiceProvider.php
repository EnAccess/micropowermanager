<?php

namespace App\Plugins\CalinSmartMeter\Providers;

use App\Lib\IManufacturerPluginProvider;
use App\Plugins\CalinSmartMeter\CalinSmartMeterApi;
use App\Plugins\CalinSmartMeter\Console\Commands\InstallPackage;
use App\Plugins\CalinSmartMeter\Models\CalinSmartTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class CalinSmartMeterServiceProvider extends ServiceProvider implements IManufacturerPluginProvider {
    public const string SLUG = 'calin_smart_meter';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Calin Smart Meter';
    }

    public function description(): string {
        return 'This plugin integrates Calin meters to Micropowermanager. It uses company_name, user_name, password and password_vend for creating tokens for energy.';
    }

    public function usageType(): string {
        return 'mini-grid';
    }

    public function tokenIssuer(): string {
        return CalinSmartMeterApi::class;
    }

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
    }
}
