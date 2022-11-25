<?php

namespace App\Services;

use App\Jobs\SmsProcessor;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;

class SmsService
{
    public const TICKET = 1;
    public const FEEDBACK = 2;
    public const DIRECTION_OUTGOING = 1;

    private $sms;
    private $transaction;

    public function __construct(
        Transaction $transaction,
        Sms $sms
    ) {
        $this->transaction = $transaction;
        $this->sms = $sms;
    }

    public function checkMessageType($message)
    {
        $wordsInMessage = explode(" ", $message);
        $firstWord = $wordsInMessage[0];
        switch (strtolower($firstWord)) {
            case 'ticket':
                return self::TICKET;
            default:
                return self::FEEDBACK;
        }
    }

    public function createAndSendSms($smsData): Sms
    {
        $sms = $this->createSms($smsData);

        $data = [
            'message' => $smsData['body'],
            'phone' => $smsData['receiver']
        ];
        $this->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);

        return $sms;
    }

    public function createSms($smsData): Sms
    {
        /** @var Sms $sms */
        $sms = $this->sms->newQuery()->create($smsData);
        return $sms;
    }

    public function sendSms($data, $smsType, $SmsConfigClass)
    {
        SmsProcessor::dispatch(
            $data,
            $smsType,
            $SmsConfigClass
        )->allOnConnection('database')->onQueue(\config('services.queues.sms'));
    }
}
