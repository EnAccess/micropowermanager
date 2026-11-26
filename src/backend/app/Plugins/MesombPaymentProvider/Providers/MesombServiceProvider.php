<?php

namespace App\Plugins\MesombPaymentProvider\Providers;

use App\Lib\IMpmPluginProvider;
use App\Plugins\MesombPaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\MesombPaymentProvider\Models\MesombTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class MesombServiceProvider extends ServiceProvider implements IMpmPluginProvider {
    public const string SLUG = 'mesomb_payment_provider';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'MeSomb Payment';
    }

    public function description(): string {
        return 'This plugin developed for getting MeSomb payments into MicroPowerManager.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(
            [
                'mesomb_transaction' => MesombTransaction::class,
            ]
        );
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->singleton(MesombTransactionProvider::class);
        $this->app->alias(MesombTransactionProvider::class, 'MesombPaymentProvider');
    }
}
