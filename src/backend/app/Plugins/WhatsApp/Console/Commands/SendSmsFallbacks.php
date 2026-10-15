<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\WhatsApp\Services\WhatsAppSmsFallbackService;

class SendSmsFallbacks extends AbstractSharedCommand {
    protected $signature = 'whatsapp:send-sms-fallbacks';
    protected $description = 'Sends SMS fallbacks for WhatsApp messages that failed or were not delivered in time';

    public function handle(WhatsAppSmsFallbackService $fallback): void {
        $result = $fallback->sendPending();

        $this->info('SMS fallbacks sent: '.$result['sent']);

        if ($result['skipped'] > 0) {
            $this->info('WhatsApp messages skipped: '.$result['skipped']);
        }
    }
}
