<?php

namespace App\Plugins\{{Plugin-Name}}\Providers;

use App\Lib\IMpmPluginProvider;
use Illuminate\Support\ServiceProvider;
use App\Plugins\{{Plugin-Name}}\Console\Commands\InstallPackage;
use App\Plugins\{{Plugin-Name}}\Providers\EventServiceProvider;
use App\Plugins\{{Plugin-Name}}\Providers\RouteServiceProvider;
use App\Plugins\{{Plugin-Name}}\Providers\ObserverServiceProvider;

class {{Plugin-Name}}ServiceProvider extends ServiceProvider implements IMpmPluginProvider
{
    public const string SLUG = '{{plugin_slug}}';

    public function slug(): string
    {
        return self::SLUG;
    }

    public function displayName(): string
    {
        return '{{Plugin Name}}';
    }

    public function description(): string
    {
        return '{{description}}';
    }

    public function usageType(): string
    {
        return 'general';
    }

    public function boot()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register()
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }
}
