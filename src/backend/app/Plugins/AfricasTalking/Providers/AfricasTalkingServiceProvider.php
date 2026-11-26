<?php

namespace App\Plugins\AfricasTalking\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\AfricasTalking\AfricasTalkingGateway;
use App\Plugins\AfricasTalking\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AfricasTalkingServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'africas_talking';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Africa\'s Talking';
    }

    public function description(): string {
        return 'This plugin developed for the communication with customers throughout Africas Talking.';
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
        $this->app->bind(AfricasTalkingGateway::class);
        $this->app->alias(AfricasTalkingGateway::class, 'AfricasTalkingGateway');
    }
}
