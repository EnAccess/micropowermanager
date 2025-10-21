<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\ApiKeyGuard;
use App\Auth\ApiKeyProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class ApiKeyServiceProvider extends ServiceProvider {
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void {
        // Register the API Key guard
        Auth::extend('api-key', fn ($app, $name, array $config): ApiKeyGuard => new ApiKeyGuard(
            $app->make(ApiKeyProvider::class),
        ));

        // Register the API Key user provider
        Auth::provider('api-key', fn ($app, array $config): ApiKeyProvider => new ApiKeyProvider(
            $app->make('request')
        ));
    }

    /**
     * Register services.
     */
    public function register(): void {}
}
