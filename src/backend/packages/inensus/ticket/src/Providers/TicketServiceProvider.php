<?php

namespace Inensus\Ticket\Providers;

use Illuminate\Support\ServiceProvider;

class TicketServiceProvider extends ServiceProvider {
    public function boot(): void {
        $this->app->register(TicketRootServiceProvider::class);
    }
}
