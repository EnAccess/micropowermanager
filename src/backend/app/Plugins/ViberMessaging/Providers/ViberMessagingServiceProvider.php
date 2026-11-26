<?php

namespace App\Plugins\ViberMessaging\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\ViberMessaging\Console\Commands\InstallPackage;
use App\Plugins\ViberMessaging\ViberGateway;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class ViberMessagingServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'viber_messaging';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Viber Messaging';
    }

    public function description(): string {
        return 'This plugin developed for the communication with customers throughout Viber messages.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(ViberGateway::class);
        $this->app->bind(ViberGateway::class, 'ViberGateway');
    }
}
