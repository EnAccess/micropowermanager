<?php

namespace App\Providers;

use App\Queue\RedisConnector;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('redis', fn (): RedisConnector => new RedisConnector($this->app['redis']));
        });
    }

    public function boot(): void {}
}
