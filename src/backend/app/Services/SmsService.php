<?php

namespace App\Services;

use App\Exceptions\NoActiveSmsProviderException;
use App\Exceptions\SmsAndroidSettingNotExistingException;
use App\Exceptions\SmsBodyParserNotExtendedException;
use App\Exceptions\SmsTypeNotFoundException;
use App\Jobs\SmsProcessor;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Sms\Senders\SmsConfigs;
use App\Sms\Senders\SmsSender;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService {
    public const TICKET = 1;
    public const FEEDBACK = 2;
    public const DIRECTION_OUTGOING = 1;

    /** Gateway errors are shown verbatim in the UI; cap them so one runaway message cannot dominate the row. */
    private const int ERROR_MESSAGE_MAX_LENGTH = 1000;

    public function __construct(
        private Sms $sms,
    ) {}

    public function checkMessageType(string $message): int {
        $wordsInMessage = explode(' ', $message);
        $firstWord = $wordsInMessage[0];

        return match (strtolower($firstWord)) {
            'ticket' => self::TICKET,
            default => self::FEEDBACK,
        };
    }

    /**
     * @param array<string, mixed> $smsData
     */
    public function createAndSendSms(array $smsData): Sms {
        $sms = $this->createSms($smsData);

        $data = [
            'message' => $smsData['body'],
            'phone' => $smsData['receiver'],
        ];
        $this->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class, $sms);

        return $sms;
    }

    /**
     * @param array<string, mixed> $smsData
     */
    public function createSms(array $smsData): Sms {
        return $this->sms->newQuery()->create($smsData);
    }

    /**
     * @param array<string, mixed>|object $data
     * @param class-string                $smsConfigs
     * @param Sms|null                    $sms        row the caller already persisted; created here when absent
     */
    public function sendSms(array|object $data, int $smsType, string $smsConfigs, ?Sms $sms = null): void {
        $uuid = Str::uuid()->toString();
        $gatewayId = null;

        try {
            $smsAndroidSettings = SmsAndroidSetting::getResponsible();
            $sender = $this->getSender($data, $smsType, $smsConfigs, $smsAndroidSettings);
            $receiver = $sender->getReceiver();
            $sender->validateReferences();

            if ($smsAndroidSettings instanceof SmsAndroidSetting) {
                $gatewayId = $smsAndroidSettings->id;
                $sender->setCallback($smsAndroidSettings->callback, $uuid);
            }
            $sender->setSms($this->resolveSmsRecord($sender, $sms, $uuid, $receiver, $gatewayId));
            dispatch(new SmsProcessor($sender));
        } catch (
            SmsTypeNotFoundException|
            SmsAndroidSettingNotExistingException|
            SmsBodyParserNotExtendedException|
            NoActiveSmsProviderException $exception) {
                Log::error('Sms send failed.', ['message : ' => $exception->getMessage()]);

                throw $exception;
            }
    }

    /**
     * Record that the gateway accepted the message. Delivery to the handset is a
     * separate, later signal that only some gateways report back.
     */
    public function markSent(Sms $sms, int $gatewayId): void {
        $this->sms->newQuery()->whereKey($sms->getKey())->update([
            'status' => Sms::STATUS_SENT,
            'gateway_id' => $gatewayId,
            'error_message' => null,
        ]);
    }

    public function markFailed(Sms $sms, \Throwable $exception): void {
        $this->sms->newQuery()->whereKey($sms->getKey())->update([
            'status' => Sms::STATUS_FAILED,
            'error_message' => Str::limit($exception->getMessage(), self::ERROR_MESSAGE_MAX_LENGTH),
            'attempts' => DB::raw('attempts + 1'),
        ]);
    }

    /**
     * @param array<string, mixed>|object $data
     * @param class-string                $smsConfigs
     */
    private function getSender(array|object $data, int $smsType, string $smsConfigs, ?SmsAndroidSetting $smsAndroidSettings): SmsSender {
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

    private function resolveSmsRecord(SmsSender $sender, ?Sms $sms, string $uuid, string $receiver, ?int $gatewayId): Sms {
        if ($sms instanceof Sms) {
            $sms->update([
                'uuid' => $uuid,
                'gateway_id' => $gatewayId,
            ]);

            return $sms;
        }

        $attributes = [
            'uuid' => $uuid,
            'body' => $sender->body,
            'receiver' => $receiver,
            'gateway_id' => $gatewayId,
            'status' => Sms::STATUS_STORED,
            'direction' => Sms::DIRECTION_OUTGOING,
        ];
        $trigger = $sender->getTriggerModel();
        if ($trigger instanceof Model) {
            $attributes['trigger_type'] = $trigger->getMorphClass();
            $attributes['trigger_id'] = $trigger->getKey();
        }

        return $this->sms->newQuery()->create($attributes);
    }
}
