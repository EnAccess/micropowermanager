<?php

namespace App\Plugins\WhatsApp\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Plugins\WhatsApp\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class SendWhatsAppTransactionListener {
    public function __construct(
        private WhatsAppNotificationService $notificationService,
    ) {}

    public function handle(TransactionSuccessfulEvent $event): void {
        try {
            $this->notificationService->sendPaymentReceipt($event->transaction);
            $this->notificationService->sendTokenDelivery($event->transaction);
        } catch (\Throwable $exception) {
            // WhatsApp must never break the payment flow; failures are recorded
            // on the message row and re-thrown only from the API boundary.
            Log::error('SendWhatsAppTransactionListener failed', [
                'transaction_id' => $event->transaction->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
