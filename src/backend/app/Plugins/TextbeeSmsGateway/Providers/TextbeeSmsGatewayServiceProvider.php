<?php

namespace App\Plugins\TextbeeSmsGateway\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\TextbeeSmsGateway\Console\Commands\FetchIncomingSms;
use App\Plugins\TextbeeSmsGateway\Console\Commands\InstallPackage;
use App\Plugins\TextbeeSmsGateway\TextbeeSmsGateway;
use Illuminate\Support\ServiceProvider;

class TextbeeSmsGatewayServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'textbee_sms_gateway';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'TextBee SMS Gateway';
    }

    public function description(): string {
        return 'This plugin developed to allow you handle your SMS needs using only your mobile device.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class, FetchIncomingSms::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(TextbeeSmsGateway::class);
        $this->app->alias(TextbeeSmsGateway::class, 'TextbeeSmsGateway');
    }
}
