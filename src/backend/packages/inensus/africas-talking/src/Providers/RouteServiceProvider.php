<?php

namespace Inensus\AfricasTalking\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider {
    protected $namespace = 'Inensus\AfricasTalking\Http\Controllers';

    public function boot() {
        parent::boot();
    }

    public function map() {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes() {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(__DIR__.'/../routes/api.php');
    }
}
