<?php

namespace App\Plugins\ChintMeter\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    protected $namespace = 'Inensus\ChintMeter\Http\Controllers';

    public function boot(): void {
        parent::boot();
    }

    public function map(): void {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(__DIR__.'/../routes/api.php');
    }
}
