<?php

namespace App\Plugins\WhatsApp\Listeners;

use App\Events\ApplianceRateReminderEvent;
use App\Plugins\WhatsApp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendWhatsAppLowBalanceListener {
    public function __construct(
        private WhatsAppNotificationService $notificationService,
    ) {}

    public function handle(ApplianceRateReminderEvent $event): void {
        try {
            $this->notificationService->sendLowBalanceWarning($event->applianceRate, $event->overdue);
        } catch (\Throwable $exception) {
            Log::error('SendWhatsAppLowBalanceListener failed', [
                'appliance_rate_id' => $event->applianceRate->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
