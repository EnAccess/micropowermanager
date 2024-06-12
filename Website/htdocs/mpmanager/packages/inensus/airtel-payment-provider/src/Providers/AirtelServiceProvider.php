<?php
namespace Inensus\AirtelPaymentProvider\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\AirtelPaymentProvider\Console\Commands\InstallPackage;
use Inensus\AirtelPaymentProvider\Providers\RouteServiceProvider;
use Inensus\AirtelPaymentProvider\Providers\ObserverServiceProvider;

class AirtelServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/airtel-payment-provider.php', 'airtel-payment-provider');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton('AirtelPaymentProvider', AirtelTransactionProvider::class);

    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/airtel-payment-provider.php' => config_path('airtel-payment-provider-integration.php'),
        ]);
    }


    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_airtel_payment_provider_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path . '*_create_airtel_payment_provider_tables.php'))) {
                    $file = $filesystem->glob($path . '*_create_airtel_payment_provider_tables.php')[0];

                    file_put_contents($file,
                        file_get_contents(__DIR__ . '/../../database/migrations/create_airtel_payment_provider_tables.php.stub'));
                }
                return $filesystem->glob($path . '*_create_airtel_payment_provider_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_airtel_payment_provider_tables.php")
            ->first();
    }
}