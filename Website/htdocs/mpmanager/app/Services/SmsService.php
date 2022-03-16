<?php

namespace App\Services;

use App\Jobs\SmsProcessor;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Sms\Senders\SmsConfigs;

class SmsService
{
    public const TICKET = 1;
    public const FEEDBACK = 2;

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

    public function createSms($smsData)
    {
        return $this->sms->newQuery()->create($smsData);
    }

    public function sendSms($data, $smsType, $SmsConfigClass)
    {
         SmsProcessor::dispatch(
             $data,
             $smsType,
             $SmsConfigClass
         )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
    }
}
