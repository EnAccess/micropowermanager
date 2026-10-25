<?php

namespace App\Plugins\WhatsApp\Jobs;

use App\Jobs\AbstractJob;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Plugins\WhatsApp\Services\WhatsAppMessageService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWhatsAppMessageJob extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * A failed send is recorded on the message row, so the worker only needs a
     * single attempt; retrying an ambiguous API response could duplicate it.
     */
    public int $tries = 1;

    public function __construct(
        private int $messageId,
        ?int $companyId = null,
    ) {
        $this->onConnection('redis');
        $this->onQueue('whatsapp');

        parent::__construct($companyId);
    }

    public function executeJob(): void {
        $messageService = app()->make(WhatsAppMessageService::class);
        $message = $messageService->find($this->messageId);

        if (!$message instanceof WhatsAppMessage) {
            return;
        }

        try {
            $messageService->deliver($message);
        } catch (\Throwable $exception) {
            // The failure is already persisted on the message row and surfaced
            // through the message-history API, so it is logged and swallowed.
            Log::error('WhatsApp message delivery failed', [
                'message_id' => $this->messageId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
