<?php

namespace App\Console\Commands;

use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResendRejectedMessages extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:resend-rejected {amount}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Takes unsent messages from the sms table and send them via the registered SMS-Provide';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(private Sms $sms) {
        parent::__construct();
    }

    public function handle(): void {
        $amountToSend = (int) $this->argument('amount');
        $messagesToSend = $this->sms
            ->where('direction', 1)
            ->where('status', -1)
            ->orderBy('id')
            ->take($amountToSend)
            ->get()->each(function ($sms) {
                Log::info("Resending rejected message {$sms->id} to {$sms->receiver}");
                $smsAndroidSettings = SmsAndroidSetting::getResponsible();
                $callback = sprintf($smsAndroidSettings->callback, $sms->uuid);
                $sms->status = 0;
                $sms->gateway_id = $smsAndroidSettings->getId();
                $sms->save();

                resolve('AndroidGateway')
                    ->sendSms(
                        $sms->receiver,
                        $sms->body,
                        $callback,
                        $smsAndroidSettings
                    );
            });
    }
}
