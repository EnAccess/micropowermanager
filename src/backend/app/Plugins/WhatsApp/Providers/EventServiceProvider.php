<?php

namespace App\Plugins\WhatsApp\Providers;

use App\Events\ApplianceRateReminderEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Plugins\WhatsApp\Listeners\SendWhatsAppLowBalanceListener;
use App\Plugins\WhatsApp\Listeners\SendWhatsAppTransactionListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        TransactionSuccessfulEvent::class => [SendWhatsAppTransactionListener::class],
        ApplianceRateReminderEvent::class => [SendWhatsAppLowBalanceListener::class],
    ];

    public function boot(): void {
        parent::boot();
    }
}
