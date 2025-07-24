<?php

namespace Inensus\SunKingSHS\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\SunKingSHS\Console\Commands\InstallPackage;
use Inensus\SunKingSHS\Modules\Api\SunKingSHSApi;

class SunKingSHSServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/sun-king-shs.php', 'sun-king-shs');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('SunKingSHSApi', SunKingSHSApi::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/sun-king-shs.php' => config_path('sun-king-shs.php'),
        ]);
    }

    public function publishVueFiles() {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path('assets/js/plugins/sun-king-shs'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_sun_king_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_sun_king_tables.php'),
            __DIR__.'/../../database/migrations/add_sun_king_transactions_table_fields.php.stub' => $this->getMigrationFileName($filesystem, 'add_sun_king_transactions_table_fields.php'),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem, string $migrationName): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem, $migrationName) {
                if (count($filesystem->glob($path.'*_'.$migrationName))) {
                    $file = $filesystem->glob($path.'*_'.$migrationName)[0];
                    file_put_contents(
                        $file,
                        file_get_contents(__DIR__.'/../../database/migrations/'.$migrationName.'.stub')
                    );
                }

                return $filesystem->glob($path.'*_'.$migrationName);
            })->push($this->app->databasePath()."/migrations/{$timestamp}_{$migrationName}")
            ->first();
    }
}
