<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider {
    /**
     * Register any application services.
     */
    public function register(): void {
        $this->hideSensitiveRequestDetails();

        $enableErrorFilter = config('telescope.enable_error_filter', false);
        if ($enableErrorFilter) {
            Telescope::filter(fn (IncomingEntry $entry): bool => $entry->isReportableException()
            || $entry->isFailedRequest()
            || $entry->isFailedJob()
            || $entry->type === 'exception');
        }
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
