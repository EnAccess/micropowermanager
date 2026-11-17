<?php

namespace App\Plugins\StsMeterVend\Providers;

use App\Models\Token;
use App\Plugins\StsMeterVend\Observers\TokenObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    public function boot(): void {
        parent::boot();
        Token::observe(TokenObserver::class);
    }
}
