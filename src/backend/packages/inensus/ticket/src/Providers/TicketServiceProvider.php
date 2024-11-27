<?php

namespace Inensus\Ticket\Providers;

use Illuminate\Support\ServiceProvider;

class TicketServiceProvider extends ServiceProvider {
    public function boot() {
        $this->app->register(TicketRootServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations();
        }
    }

    public function register() {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/tickets.php',
            'tickets'
        );
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/tickets.php' => config_path('tickets.php'),
        ], 'config');
    }

    public function publishMigrations() {
        if (!class_exists('CreateTicketingTables')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../../database/migrations/create_ticketing_tables.php.stub' => $this->app->databasePath()."/migrations/{$timestamp}_create_ticketing_tables.php",
            ], 'migrations');
        }
    }
}
