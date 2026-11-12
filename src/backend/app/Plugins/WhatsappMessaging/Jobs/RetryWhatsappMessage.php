<?php

namespace App\Plugins\WhatsappMessaging\Jobs;

use App\Jobs\AbstractJob;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use App\Services\SmsService;

/**
 * Sends a WhatsApp message a second time after a temporary Meta failure, and by SMS when that fails too.
 * It carries the id instead of the Sms model because the tenant database is only connected once the job runs.
 */
class RetryWhatsappMessage extends AbstractJob {
    public const int DELAY_MINUTES = 5;

    // This job already is the retry; a second queue attempt would send the WhatsApp message or the SMS twice.
    public int $tries = 1;

    /** @param array<string, string> $bodyParserValues */
    public function __construct(
        private int $smsId,
        private string $body,
        private string $receiver,
        private ?string $bodyParser,
        private array $bodyParserValues,
    ) {
        $this->onConnection('redis');
        $this->onQueue('sms');
        $this->delay(now()->addMinutes(self::DELAY_MINUTES));

        parent::__construct();
    }

    public function executeJob(): void {
        $sms = Sms::query()->findOrFail($this->smsId);
        $whatsappGateway = app()->make(WhatsappMessagingGateway::class);

        if ($whatsappGateway->isAvailable() && $whatsappGateway->trySend($sms, $this->body, $this->receiver, $this->bodyParser, $this->bodyParserValues, isRetry: true)) {
            return;
        }

        app()->make(SmsService::class)->sendViaSmsGateway($sms, $this->body, $this->receiver);
    }

    protected function handleFailure(\Throwable $throwable): void {
        $sms = Sms::query()->find($this->smsId);

        if ($sms instanceof Sms) {
            app()->make(SmsService::class)->markFailed($sms, $throwable);
        }
    }
}
