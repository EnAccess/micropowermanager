<?php

namespace App\Plugins\EgoSmsGateway\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\EgoSmsGateway\Console\Commands\InstallPackage;
use App\Plugins\EgoSmsGateway\EgoSmsGateway;
use Illuminate\Support\ServiceProvider;

class EgoSmsGatewayServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'ego_sms_gateway';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'EgoSMS Gateway';
    }

    public function description(): string {
        return 'Send SMS through EgoSMS, a bulk SMS provider in Uganda.';
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
        $this->app->bind(EgoSmsGateway::class);
        $this->app->alias(EgoSmsGateway::class, 'EgoSmsGateway');
    }
}
