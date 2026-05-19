<?php

namespace App\Plugins\PesapalPaymentProvider\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * @var array<int, string>
     */
    protected $subscribe = [
    ];

    public function boot(): void {
        parent::boot();
    }
}
