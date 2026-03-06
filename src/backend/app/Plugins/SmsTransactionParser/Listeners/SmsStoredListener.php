<?php

declare(strict_types=1);

namespace App\Plugins\SmsTransactionParser\Listeners;

use App\Events\SmsStoredEvent;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Plugins\SmsTransactionParser\Services\SmsTransactionService;
use Illuminate\Support\Facades\Log;

class SmsStoredListener {
    public function __construct(
        private SmsTransactionService $smsTransactionService,
    ) {}

    public function handle(SmsStoredEvent $event): void {
        $result = $this->smsTransactionService->processIncomingSms(
            $event->message,
            $event->sender,
        );

        if ($result instanceof SmsTransaction) {
            Log::info('SMS transaction created from incoming SMS', [
                'reference' => $result->transaction_reference,
                'provider' => $result->provider_name,
            ]);
        }
    }
}
