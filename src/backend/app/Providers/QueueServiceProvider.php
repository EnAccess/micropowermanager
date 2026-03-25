<?php

namespace App\Providers;

use App\Queue\Dispatcher;
use App\Queue\RedisConnector;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->app->afterResolving('queue', function ($manager) {
            $manager->addConnector('redis', fn (): RedisConnector => new RedisConnector($this->app['redis']));
        });
    }

    public function boot(): void {
        $this->app->extend(
            \Illuminate\Contracts\Bus\Dispatcher::class,
            fn ($dispatcher): Dispatcher => new Dispatcher($dispatcher),
        );

        $this->app->extend(
            QueueingDispatcher::class,
            fn ($dispatcher): Dispatcher => new Dispatcher($dispatcher),
        );
    }
}
