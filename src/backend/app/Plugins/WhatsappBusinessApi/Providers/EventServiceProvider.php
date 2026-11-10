<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Providers;

use App\Events\TransactionFailedEvent;
use App\Plugins\WhatsappBusinessApi\Listeners\TransactionFailedWhatsappListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        TransactionFailedEvent::class => [
            TransactionFailedWhatsappListener::class,
        ],
    ];

    /**
     * @var array<class-string>
     */
    protected $subscribe = [];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
