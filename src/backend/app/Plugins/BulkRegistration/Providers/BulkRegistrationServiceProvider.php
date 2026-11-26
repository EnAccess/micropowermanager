<?php

namespace App\Plugins\BulkRegistration\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\BulkRegistration\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class BulkRegistrationServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'bulk_registration';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Bulk Registration';
    }

    public function description(): string {
        return 'This plugin provides bulk registration of the company\'s existing records. NOTE: Please do not use this plugin to register your Spark & Stemaco meter records. These records will be synchronized automatically once you configure your credential settings for these plugins.';
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
    }
}
