<?php

namespace Inensus\SunKingSHS\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\SunKingSHS\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Inensus\SunKingSHS\Modules\Api\SunKingSHSApi;


class SunKingSHSServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/sun-king-shs.php', 'sun-king-shs');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('SunKingSHSApi', SunKingSHSApi::class);
    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/sun-king-shs.php' => config_path('sun-king-shs.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/sun-king-shs'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_sun_king_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }


    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path . '*_create_sun_king_tables.php'))) {
                    $file = $filesystem->glob($path . '*_create_sun_king_tables.php')[0];
                    file_put_contents($file,
                        file_get_contents(__DIR__ . '/../../database/migrations/create_sun_king_tables.php.stub'));
                }
                return $filesystem->glob($path . '*_create_sun_king_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_sun_king_tables.php")
            ->first();
    }
}