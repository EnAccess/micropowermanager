<?php

namespace App\Plugins\StsMeterVend\Providers;

use App\Lib\IVendingPluginProvider;
use App\Plugins\StsMeterVend\Console\Commands\InstallPackage;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class StsMeterVendServiceProvider extends ServiceProvider implements IVendingPluginProvider {
    public const string SLUG = 'sts_meter_vend';

    public function slug(): string {
        return self::SLUG;
    }

    public function displayName(): string {
        return 'STS Meter Vend';
    }

    public function description(): string {
        return 'Vends IEC 62055-41 STS tokens for registered STS meters through a self-hosted nxt-sts service.';
    }

    public function usageType(): string {
        return 'general';
    }

    public function tokenIssuer(): string {
        return StsMeterApi::class;
    }

    public function boot(): void {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
        Relation::morphMap(['sts_transaction' => StsTransaction::class]);
    }

    public function register(): void {
        $this->app->register(ObserverServiceProvider::class);
    }
}
