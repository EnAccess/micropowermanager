<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Request;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('development');

        Telescope::filter(fn (IncomingEntry $entry): bool => $isLocal
               || $entry->isReportableException()
               || $entry->isFailedRequest()
               || $entry->isFailedJob()
               || $entry->isScheduledTask()
               || $entry->hasMonitoredTag());
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void {
        if ($this->app->environment('development')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void {
        Gate::define('viewTelescope', fn ($user = null): bool => true);
    }
}
