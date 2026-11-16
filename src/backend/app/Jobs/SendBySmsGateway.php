<?php

namespace App\Jobs;

use App\Models\Sms;
use App\Services\SmsService;

/**
 * The SMS fallback after WhatsApp could not deliver: queued at once, so the customer is not left waiting for the next sms:resend-rejected run.
 * It carries the id instead of the Sms model because the tenant database is only connected once the job runs.
 */
class SendBySmsGateway extends AbstractJob {
    public int $tries = 1;

    public function __construct(private int $smsId) {
        $this->onConnection('redis');
        $this->onQueue('sms');

        parent::__construct();
    }

    public function executeJob(): void {
        app()->make(SmsService::class)->sendViaSmsGateway(Sms::query()->findOrFail($this->smsId));
    }

    protected function handleFailure(\Throwable $throwable): void {
        $sms = Sms::query()->find($this->smsId);

        if ($sms instanceof Sms) {
            app()->make(SmsService::class)->markFailed($sms, $throwable);
        }
    }
}
