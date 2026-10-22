<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Providers;

use App\Models\Address\Address;
use App\Plugins\WhatsappBusinessApi\Observers\AddressObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
        Address::observe(AddressObserver::class);
    }
}
