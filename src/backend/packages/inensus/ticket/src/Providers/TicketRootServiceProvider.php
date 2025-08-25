<?php

namespace Inensus\Ticket\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class TicketRootServiceProvider extends ServiceProvider {
    protected $namespace = 'Inensus\Ticket\Http\Controllers';

    public function boot() {
        parent::boot();
    }

    public function map(): void {
        $this->mapApiRoutes();
    }

    protected function mapApiRoutes(): void {
        Route::prefix('tickets')
            ->namespace($this->namespace)
            ->group(__DIR__.'/../routes/web.php');
    }
}
