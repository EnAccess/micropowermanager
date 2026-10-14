<?php

namespace App\Plugins\WhatsApp\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    /**
     * Define the routes for the application.
     */
    public function boot(): void {
        parent::boot();
    }

    /**
     * Map routes.
     */
    public function map(): void {
        $this->mapApiRoutes();
    }

    /**
     * Define the "api" routes for the plugin.
     */
    protected function mapApiRoutes(): void {
        Route::prefix('api')
            ->middleware('api')
            ->group(__DIR__.'/../routes/api.php');
    }
}
