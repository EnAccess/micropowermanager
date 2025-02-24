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
use Illuminate\Support\Str;

class SmsService {
    public const TICKET = 1;
    public const FEEDBACK = 2;
    public const DIRECTION_OUTGOING = 1;

    public function __construct(
        private Sms $sms,
    ) {}

    public function checkMessageType($message) {
        $wordsInMessage = explode(' ', $message);
        $firstWord = $wordsInMessage[0];
        switch (strtolower($firstWord)) {
            case 'ticket':
                return self::TICKET;
            default:
                return self::FEEDBACK;
        }
    }

    public function createAndSendSms($smsData): Sms {
        $sms = $this->createSms($smsData);

        $data = [
            'message' => $smsData['body'],
            'phone' => $smsData['receiver'],
        ];
        $this->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);

        return $sms;
    }

    public function createSms($smsData): Sms {
        /** @var Sms $sms */
        $sms = $this->sms->newQuery()->create($smsData);

        return $sms;
    }

    public function sendSms($data, $smsType, $smsConfigs) {
        $uuid = Str::uuid()->toString();
        $gatewayId = null;

        try {
            $smsAndroidSettings = SmsAndroidSetting::getResponsible();
            $sender = $this->getSender($data, $smsType, $smsConfigs, $smsAndroidSettings);
            $receiver = $sender->getReceiver();
            $sender->validateReferences();

            if ($smsAndroidSettings) {
                $gatewayId = $smsAndroidSettings->getId();
                $sender->setCallback($smsAndroidSettings->callback, $uuid);
            }
            $this->associateSmsWithForSmsType($sender, $data, $uuid, $receiver, $gatewayId);
            SmsProcessor::dispatch($sender)->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
        } catch (
            SmsTypeNotFoundException|
            SmsAndroidSettingNotExistingException|
            SmsBodyParserNotExtendedException $exception) {
                Log::error('Sms send failed.', ['message : ' => $exception->getMessage()]);

                throw $exception;
            }
    }

    private function getSender($data, $smsType, $smsConfigs, $smsAndroidSettings) {
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

    private function associateSmsWithForSmsType($sender, $data, $uuid, $receiver, $gatewayId) {
        if (!($sender instanceof ManualSms)) {
            Sms::query()->create([
                'uuid' => $uuid,
                'body' => $sender->body,
                'receiver' => $receiver,
                'gateway_id' => $gatewayId,
                'status' => Sms::STATUS_STORED,
                'direction' => Sms::DIRECTION_OUTGOING,
            ]);
        } else {
            $lastSentManualSms = Sms::query()->where('receiver', $receiver)->where(
                'body',
                $sender->body
            )->latest()->first();
            if ($lastSentManualSms) {
                $lastSentManualSms->update([
                    'uuid' => $uuid,
                    'gateway_id' => $gatewayId,
                ]);
            }
        }
    }
}
