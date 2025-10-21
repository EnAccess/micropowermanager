<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        parent::boot();

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        $slackWebhook = config('horizon.notifications.slack_webhook_url');

        if (!empty($slackWebhook)) {
            Horizon::routeSlackNotificationsTo($slackWebhook);
        }
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access Horizon in non-local environments.
     */
    protected function gate(): void {
        Gate::define('viewHorizon', function ($user = null): bool {
            $horizon_username = config('horizon.http_basic_auth.username');
            $horizon_password = config('horizon.http_basic_auth.password');

            if (app()->environment('development')) {
                return true;
            }

            if (empty($horizon_username) || empty($horizon_password)) {
                abort(403, 'Horizon access not configured.');
            }

            // Prompt for HTTP Basic Auth
            if (!isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
                header('WWW-Authenticate: Basic realm="Horizon"');
                header('HTTP/1.0 401 Unauthorized');
                exit;
            }

            return $_SERVER['PHP_AUTH_USER'] === $horizon_username
                && $_SERVER['PHP_AUTH_PW'] === $horizon_password;
        });
    }
}
