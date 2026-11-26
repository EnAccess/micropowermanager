<?php

namespace App\Plugins\AirtelSmsGateway\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\AirtelSmsGateway\AirtelSmsGateway;
use App\Plugins\AirtelSmsGateway\Console\Commands\InstallPackage;
use Illuminate\Support\ServiceProvider;

class AirtelSmsGatewayServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'airtel_sms_gateway';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Airtel SMS Gateway';
    }

    public function description(): string {
        return 'Send SMS through an Airtel bulk SMS account over SMPP.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(AirtelSmsGateway::class);
        $this->app->alias(AirtelSmsGateway::class, 'AirtelSmsGateway');
    }
}
