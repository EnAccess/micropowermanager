<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\WaveMoneyPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class WaveMoneyPaymentProviderServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'wave_money_payment_provider';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'Wave Money Payment';
    }

    public function description(): string {
        return 'This plugin developed for getting WaveMoney payments into MicroPowerManager.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                WaveMoneyTransaction::RELATION_NAME => WaveMoneyTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(WaveMoneyTransactionProvider::class);
        $this->app->alias(WaveMoneyTransactionProvider::class, 'WaveMoneyPaymentProvider');
    }
}
