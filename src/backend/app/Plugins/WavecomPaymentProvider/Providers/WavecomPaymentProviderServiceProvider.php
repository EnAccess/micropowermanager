<?php

namespace App\Plugins\WavecomPaymentProvider\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\WavecomPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\WavecomPaymentProvider\Models\WaveComTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class WavecomPaymentProviderServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'wavecom_payment_provider';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Wavecom Payment';
    }

    public function description(): string {
        return 'This plugin developed for getting Wavecom(Senegal) payments into MicroPowerManager.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                WaveComTransaction::RELATION_NAME => WaveComTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind(WaveComTransactionProvider::class);
        $this->app->alias(WaveComTransactionProvider::class, 'WaveComPaymentProvider');
    }
}
