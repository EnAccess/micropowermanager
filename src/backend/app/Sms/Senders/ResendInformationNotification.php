<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Sms\BodyParsers\ResendInformation;
use App\Sms\BodyParsers\ResendInformationLastTransactionNotFound;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class ResendInformationNotification extends SmsSender {
    protected mixed $data;
    public string $body = '';
    protected ?array $references = [
        'header' => 'SmsResendInformationHeader',
        'footer' => 'SmsResendInformationFooter',
        'body' => 'ResendInformation',
    ];

    public function prepareBody(): void {
        if (!is_array($this->data)) {
            try {
                $smsBody = $this->smsBodyService->getSmsBodyByReference('ResendInformation');
            } catch (ModelNotFoundException $exception) {
                $exception = new MissingSmsReferencesException('ResendInformation SMS body
                record not found in database');
                Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }

            $this->data->paymentHistories()->each(function ($payment) use ($smsBody) {
                $smsObject = new ResendInformation($payment);
                try {
                    $this->body .= $smsObject->parseSms($smsBody->body);
                } catch (\Exception $exception) {
                    Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);

                    return;
                }
            });
        } else {
            try {
                $smsBody = $this->smsBodyService->getSmsBodyByReference('ResendInformationLastTransactionNotFound');
            } catch (ModelNotFoundException $exception) {
                $exception = new MissingSmsReferencesException('ResendInformation SMS body
                record not found in database');
                Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
            $smsObject = new ResendInformationLastTransactionNotFound($this->data);
            try {
                $this->body .= $smsObject->parseSms($smsBody->body);
            } catch (\Exception $exception) {
                Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);

                return;
            }
        }
    }
}
