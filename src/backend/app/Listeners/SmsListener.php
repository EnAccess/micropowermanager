<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use App\Services\SmsResendInformationKeyService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class SmsListener {
    private $smsResendInformationKeyService;
    private $transaction;
    private $smsService;

    public function __construct(
        SmsResendInformationKeyService $smsResendInformationKeyService,
        Transaction $transaction,
        SmsService $smsService,
    ) {
        $this->smsResendInformationKeyService = $smsResendInformationKeyService;
        $this->transaction = $transaction;
        $this->smsService = $smsService;
    }

    public function onSmsStored($sender, $message) {
        $resendInformationKey = $this->smsResendInformationKeyService->getResendInformationKeys()->first();

        if (!$resendInformationKey) {
            return;
        }

        if (strpos(strtolower($message), strtolower($resendInformationKey->key)) !== false) {
            $wordsInMessage = explode(' ', $message);
            $meterSerial = end($wordsInMessage);

            try {
                $transaction = $this->transaction->newQuery()->with('paymentHistories', 'device.person')
                    ->where('message', $meterSerial)->latest()->firstOrFail();

                $this->smsService->sendSms($transaction, SmsTypes::RESEND_INFORMATION, SmsConfigs::class);
            } catch (\Exception $ex) {
                Log::error('Sms resend failed to '.$sender, ['message : ' => $ex->getMessage()]);
            }
        }
    }

    public function subscribe(Dispatcher $events) {
        $events->listen('sms.stored', 'App\Listeners\SmsListener@onSmsStored');
    }
}
