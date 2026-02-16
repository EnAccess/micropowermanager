<?php

namespace App\Jobs;

use App\Exceptions\NoActiveSmsProviderException;
use App\Exceptions\SmsAndroidSettingNotExistingException;
use App\Exceptions\SmsBodyParserNotExtendedException;
use App\Exceptions\SmsRecordNotFoundException;
use App\Exceptions\SmsTypeNotFoundException;
use App\Sms\Senders\SmsSender;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SmsProcessor extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private SmsSender $smsSender) {
        $this->onConnection('redis');
        $this->onQueue('sms');

        parent::__construct();
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            $this->smsSender->sendSms();
        } catch (SmsTypeNotFoundException|SmsAndroidSettingNotExistingException|SmsBodyParserNotExtendedException|NoActiveSmsProviderException|SmsRecordNotFoundException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);

            return;
        }
    }
}
