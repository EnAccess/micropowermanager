<?php

namespace App\Services;

use App\Console\Commands\ResendRejectedMessages;
use App\Exceptions\NoActiveSmsProviderException;
use App\Exceptions\SmsBodyParserNotExtendedException;
use App\Exceptions\SmsTypeNotFoundException;
use App\Jobs\SendBySmsGateway;
use App\Jobs\SmsProcessor;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
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

    // The first try and one retry; with an SMS gateway WhatsApp is never retried, because the SMS goes out at once instead.
    public const int MAX_WHATSAPP_ATTEMPTS = 2;

    /** Gateway errors are shown verbatim in the UI; cap them so one runaway message cannot dominate the row. */
    private const int ERROR_MESSAGE_MAX_LENGTH = 1000;

    public function __construct(
        private Sms $sms,
        private SmsAttempt $smsAttempt,
        private SmsGatewayResolverService $gatewayResolver,
        private WhatsappMessagingGateway $whatsappGateway,
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
     * @param array<string, mixed>  $smsData
     * @param array<string, string> $templateValues
     */
    public function createAndSendSms(array $smsData, ?string $templateId = null, array $templateValues = []): Sms {
        $sms = $this->createSms($smsData);

        $data = [
            'message' => $smsData['body'],
            'phone' => $smsData['receiver'],
            'template_id' => $templateId,
            'template_values' => $templateValues,
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

        try {
            $sender = $this->getSender($data, $smsType, $smsConfigs);
            $receiver = $sender->getReceiver();
            $sender->validateReferences();

            $sender->setSms($this->resolveSmsRecord($sender, $sms, $uuid, $receiver, null));
            dispatch(new SmsProcessor($sender));
        } catch (
            SmsTypeNotFoundException|
            SmsBodyParserNotExtendedException|
            NoActiveSmsProviderException $exception) {
                Log::error('Sms send failed.', ['message : ' => $exception->getMessage()]);

                throw $exception;
            }
    }

    /**
     * WhatsApp is tried before the SMS gateway, not instead of it: a customer WhatsApp cannot reach, or a WhatsApp failure, ends on the SMS gateway.
     *
     * @param array<string, string> $values the body parser's values, or the operator's values for $templateId
     *
     * @throws NoActiveSmsProviderException
     */
    public function deliver(Sms $sms, string $receiver, ?string $bodyParserReference, array $values, ?string $templateId = null): void {
        try {
            if ($this->whatsappGateway->isAvailable() && $this->whatsappGateway->send($sms, $receiver, $bodyParserReference, $values, $templateId)) {
                $this->markSent($sms, MpmPlugin::WHATSAPP_MESSAGING);

                return;
            }
        } catch (WhatsappApiException|WhatsappTemplateNotConfiguredException $exception) {
            $this->whatsappFailed($sms, $exception->getMessage(), $exception instanceof WhatsappApiException && $exception->isTemporary());

            return;
        }

        $this->sendViaSmsGateway($sms);
    }

    /**
     * Called when sending fails and when Meta's webhook later reports a failure.
     * Without an SMS gateway, WhatsApp is the only way to reach the customer, so a temporary error gets one more WhatsApp try.
     */
    public function whatsappFailed(Sms $sms, string $error, bool $isTemporary): void {
        if ($this->gatewayResolver->isSmsGatewayConfigured()) {
            $this->updateRow($sms, ['status' => Sms::STATUS_SENDING_BY_SMS, 'error_message' => $error]);
            dispatch(new SendBySmsGateway($sms->id));

            return;
        }

        $whatsappAttempts = $sms->deliveryAttempts()->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->count();
        $this->updateRow($sms, [
            'status' => $isTemporary && $whatsappAttempts < self::MAX_WHATSAPP_ATTEMPTS ? Sms::STATUS_WAITING_FOR_RETRY : Sms::STATUS_FAILED,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'error_message' => $error,
        ]);
    }

    /**
     * Sends through the SMS gateway configured in Main Settings, never through WhatsApp. A refusal is recorded on the row,
     * where sms:resend-rejected picks it up.
     *
     * @return bool whether the gateway accepted the message
     *
     * @throws NoActiveSmsProviderException
     */
    public function sendViaSmsGateway(Sms $sms): bool {
        [$gateway, $viberId] = $this->gatewayResolver->determineGateway($sms->receiver);

        $resolved = $this->gatewayResolver->resolveGatewayAndArgs($gateway, $sms, [
            'body' => $sms->body,
            'receiver' => $sms->receiver,
            'viberId' => $viberId,
        ]);

        try {
            $resolved['gateway']->sendSms(...$resolved['args']);
        } catch (\Exception $exception) {
            $this->recordGatewayAttempt($sms, $resolved['gatewayId'], $exception);
            $this->markFailed($sms, $exception, $resolved['gatewayId']);

            return false;
        }

        $this->recordGatewayAttempt($sms, $resolved['gatewayId'], null);
        $this->updateRow($sms, ['attempts' => DB::raw('attempts + 1')]);
        $this->markSent($sms, $resolved['gatewayId']);

        return true;
    }

    /**
     * Record that the gateway accepted the message. Delivery to the handset is a
     * separate, later signal that only some gateways report back.
     */
    public function markSent(Sms $sms, int $gatewayId): void {
        $this->updateRow($sms, [
            'status' => Sms::STATUS_SENT,
            'gateway_id' => $gatewayId,
            'error_message' => null,
        ]);
    }

    /**
     * An SMS gateway try that did not go through. attempts counts these tries only, so a WhatsApp failure never uses up the SMS resend.
     */
    public function markFailed(Sms $sms, \Throwable $exception, ?int $gatewayId = null): void {
        $this->updateRow($sms, array_filter([
            'status' => $sms->attempts + 1 < ResendRejectedMessages::MAX_ATTEMPTS ? Sms::STATUS_WAITING_FOR_RETRY : Sms::STATUS_FAILED,
            'gateway_id' => $gatewayId,
            'error_message' => Str::limit($exception->getMessage(), self::ERROR_MESSAGE_MAX_LENGTH),
            'attempts' => DB::raw('attempts + 1'),
        ], fn (mixed $value): bool => $value !== null));
    }

    private function recordGatewayAttempt(Sms $sms, int $gatewayId, ?\Throwable $exception): void {
        $this->smsAttempt->newQuery()->create([
            'sms_id' => $sms->id,
            'gateway_id' => $gatewayId,
            'status' => $exception instanceof \Throwable ? SmsAttempt::STATUS_FAILED : SmsAttempt::STATUS_ACCEPTED,
            'error_message' => $exception instanceof \Throwable ? Str::limit($exception->getMessage(), self::ERROR_MESSAGE_MAX_LENGTH) : null,
            'failed_at' => $exception instanceof \Throwable ? now() : null,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function updateRow(Sms $sms, array $attributes): void {
        $this->sms->newQuery()->whereKey($sms->getKey())->update($attributes);
        $sms->refresh();
    }

    /**
     * @param array<string, mixed>|object $data
     * @param class-string                $smsConfigs
     */
    private function getSender(array|object $data, int $smsType, string $smsConfigs): SmsSender {
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
