<?php

namespace App\Providers;

use App\Services\ISolarService;
use App\Services\IUserService;
use App\Services\IWeatherDataProvider;
use App\Services\OpenWeatherMap;
use App\Services\SolarService;
use App\Services\UserService;
use Illuminate\Support\ServiceProvider;

class ServicesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Bind UserService to IUserService
        $this->app->bind(IUserService::class, UserService::class);
        $this->app->bind(ISolarService::class, SolarService::class);

        //Bind open weather map service   to IWeatherDataProvider interface
        $this->app->bind(IWeatherDataProvider::class, OpenWeatherMap::class);
    }
}
