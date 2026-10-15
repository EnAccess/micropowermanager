<?php

namespace App\Plugins\WhatsApp\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\WhatsApp\Services\WhatsAppSmsFallbackService;

class SendSmsFallbacks extends AbstractSharedCommand {
    protected $signature = 'whatsapp:send-sms-fallbacks
                            {--force : Force fallback for undelivered messages without waiting for timeout window}
                            {--timeout= : Custom timeout in minutes (defaults to config value)}
                            {--limit=100 : Maximum messages to process in batch}';
    protected $description = 'Sends SMS fallbacks for WhatsApp messages that failed or were not delivered in time';

    public function handle(WhatsAppSmsFallbackService $fallback): void {
        $force = (bool) $this->option('force');
        $timeout = $this->option('timeout') !== null ? (int) $this->option('timeout') : null;
        $limit = (int) $this->option('limit');

        $result = $fallback->sendPending($limit, $timeout, $force);

        $this->info('SMS fallbacks sent: '.$result['sent']);

        if ($result['skipped'] > 0) {
            $this->info('WhatsApp messages skipped: '.$result['skipped']);
        }
    }
}
