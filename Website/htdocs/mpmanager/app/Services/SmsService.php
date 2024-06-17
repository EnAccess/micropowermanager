<?php

namespace App\Services;

use App\Exceptions\SmsAndroidSettingNotExistingException;
use App\Exceptions\SmsBodyParserNotExtendedException;
use App\Exceptions\SmsTypeNotFoundException;
use App\Jobs\SmsProcessor;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Sms\Senders\ManualSms;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\SmsSender;
use App\Sms\SmsTypes;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public const TICKET = 1;
    public const FEEDBACK = 2;
    public const DIRECTION_OUTGOING = 1;

    public function __construct(
        private Sms $sms
    ) {
    }

    public function checkMessageType($message)
    {
        $wordsInMessage = explode(' ', $message);
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
            'phone' => $smsData['receiver'],
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

    private function getSmsAndroidSettings()
    {
        try {
            return SmsAndroidSetting::getResponsible();
        } catch (SmsAndroidSettingNotExistingException $exception) {
            throw $exception;
        }
    }

    public function sendSms($data, $smsType, $smsConfigs)
    {
        try {
            $smsAndroidSettings = $this->getSmsAndroidSettings();
            $smsType = $this->resolveSmsType($data, $smsType, $smsConfigs, $smsAndroidSettings);
        } catch (SmsTypeNotFoundException|SmsAndroidSettingNotExistingException|SmsBodyParserNotExtendedException $exception) {
            Log::critical('Sms send failed.', ['message : ' => $exception->getMessage()]);

            return;
        }
        $receiver = $smsType->getReceiver();
        // set the uuid for the callback
        $uuid = $smsType->generateCallbackAndGetUuid($smsAndroidSettings->callback);
        try {
            // sends sms or throws exception
            $smsType->validateReferences();
        } catch (\Exception $e) {
            Log::error('Sms Service failed '.$receiver, ['reason' => $e->getMessage()]);
            throw $e;
        }
        SmsProcessor::dispatch($smsType)->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
        $this->associateSmsWithForSmsType($smsType, $data, $uuid, $receiver, $smsAndroidSettings);
    }

    private function resolveSmsType($data, $smsType, $smsConfigs, $smsAndroidSettings)
    {
        $configs = resolve($smsConfigs);
        if (!array_key_exists($smsType, $configs->smsTypes)) {
            throw new SmsTypeNotFoundException('SmsType could not resolve.');
        }
        $smsBodyService = resolve($configs->servicePath);
        $reflection = new \ReflectionClass($configs->smsTypes[$smsType]);

        if (!$reflection->isSubclassOf(SmsSender::class)) {
            throw new SmsBodyParserNotExtendedException('SmsBodyParser has not extended.');
        }

        return $reflection->newInstanceArgs([
            $data,
            $smsBodyService,
            $configs->bodyParsersPath,
            $smsAndroidSettings,
        ]);
    }

    private function associateSmsWithForSmsType($smsType, $data, $uuid, $receiver, $smsAndroidSettings)
    {
        if (!($smsType instanceof ManualSms)) {
            $sms = Sms::query()->make([
                'uuid' => $uuid,
                'body' => $smsType->body,
                'receiver' => $receiver,
                'gateway_id' => $smsAndroidSettings->getId(),
            ]);
            $sms->trigger()->associate($data);
            $sms->save();
        } else {
            $lastSentManualSms = Sms::query()->where('receiver', $receiver)->where(
                'body',
                $smsType->body
            )->latest()->first();
            if ($lastSentManualSms) {
                $lastSentManualSms->update([
                    'uuid' => $uuid,
                    'gateway_id' => $smsAndroidSettings->getId(),
                ]);
            }
        }
    }
}
