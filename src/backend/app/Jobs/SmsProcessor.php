<?php

namespace App\Jobs;

use App\Exceptions\SmsAndroidSettingNotExistingException;
use App\Exceptions\SmsBodyParserNotExtendedException;
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
     *
     * @param $data
     * @param $smsConfigs
     */
    public function __construct(private SmsSender $smsSender) {
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob(): void {
        try {
            $this->smsSender->sendSms();
        } catch (SmsTypeNotFoundException|SmsAndroidSettingNotExistingException|SmsBodyParserNotExtendedException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);

            return;
        }
    }
}
