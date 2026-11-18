<?php

namespace App\Plugins\OpenPaygo\Providers;

use App\Plugins\OpenPaygo\Console\Commands\InstallPackage;
use App\Plugins\OpenPaygo\Console\Commands\RecoverOpenPaygoIssuances;
use App\Plugins\OpenPaygo\OpenPaygoApi;
use Illuminate\Support\ServiceProvider;

class OpenPaygoServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->commands([InstallPackage::class, RecoverOpenPaygoIssuances::class]);
    }

    public function register(): void {
        $this->app->bind(OpenPaygoApi::class);
        $this->app->alias(OpenPaygoApi::class, 'OpenPaygoApi');
    }
}
