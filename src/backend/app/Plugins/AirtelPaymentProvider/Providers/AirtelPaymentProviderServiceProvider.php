<?php

namespace App\Plugins\AirtelPaymentProvider\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\AirtelPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\AirtelPaymentProvider\Models\AirtelTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AirtelPaymentProviderServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'airtel_payment_provider';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Airtel Money';
    }

    public function description(): string {
        return 'Accept Airtel Money payments in Tanzania and Uganda. Airtel calls MicroPowerManager to validate, process and enquire about each payment.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);

        Relation::morphMap([
            AirtelTransaction::RELATION_NAME => AirtelTransaction::class,
        ]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }
}
