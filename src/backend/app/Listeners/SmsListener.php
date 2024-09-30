<?php

namespace App\Listeners;

use App\Models\Transaction\Transaction;
use App\Services\SmsResendInformationKeyService;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Events\Dispatcher;

class SmsListener
{
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

    public function onSmsStored($sender, $message)
    {
        $resendInformationKey = $this->smsResendInformationKeyService->getResendInformationKeys()->first();
        if (!$resendInformationKey) {
            return;
        }
        $resend = strpos(strtolower($message), strtolower($resendInformationKey));
        if (!$resend) {
            return;
        }
        $wordsInMessage = explode(' ', $message);
        $meterSerial = end($wordsInMessage);
        try {
            $transaction = $this->transaction->newQuery()->with('paymentHistories')
                ->where('message', $meterSerial)->latest()->firstOrFail();
            $this->smsService->sendSms($transaction, SmsTypes::RESEND_INFORMATION, SmsConfigs::class);
        } catch (ModelNotFoundException $ex) {
            $data = [
                'phone' => $sender,
                'meter' => $meterSerial,
            ];
            $this->smsService->sendSms($data, SmsTypes::RESEND_INFORMATION, SmsConfigs::class);
        }
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('sms.stored', 'App\Listeners\SmsListener@onSmsStored');
    }
}
