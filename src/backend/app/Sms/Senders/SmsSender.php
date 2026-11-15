<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Models\ApplianceRate;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

abstract class SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = null;
    public string $body = '';

    // Plain values rather than the parser object, because the sender is serialised into the SmsProcessor queue job.
    public ?string $bodyParserReference = null;

    /** @var array<string, string> */
    public array $bodyParserValues = [];

    protected ?string $receiver = null;
    private ?Sms $sms = null;

    public function __construct(protected mixed $data, protected mixed $smsBodyService, protected string $parserSubPath) {}

    public function setSms(Sms $sms): void {
        $this->sms = $sms;
    }

    /**
     * The row this send writes its outcome to. Null only while the sender is
     * being prepared, before SmsService has persisted it.
     */
    public function getSms(): ?Sms {
        return $this->sms;
    }

    public function sendSms(): void {
        $sms = $this->sms;

        if (!$sms instanceof Sms) {
            throw new \LogicException(static::class.' was dispatched without an Sms record to report against.');
        }

        $smsService = app()->make(SmsService::class);
        $whatsappGateway = app()->make(WhatsappMessagingGateway::class);

        if (
            $whatsappGateway->isAvailable()
            && $whatsappGateway->trySend($sms, $this->body, (string) $this->receiver, $this->bodyParserReference, $this->bodyParserValues)
        ) {
            $smsService->markSent($sms, MpmPlugin::WHATSAPP_MESSAGING);

            return;
        }

        $smsService->sendViaSmsGateway($sms, $this->body, (string) $this->receiver);
    }

    /**
     * The sms_bodies references this sender renders as its whole body through one parser, which is what a channel needs to register them as message templates.
     * Read from the class defaults because a sender cannot be built without the data it reports on.
     *
     * @return list<string>
     */
    public static function bodyReferences(): array {
        $reference = new \ReflectionClass(static::class)->getDefaultProperties()['references']['body'] ?? '';

        return $reference === '' ? [] : [$reference];
    }

    public function prepareHeader(): void {
        try {
            $smsBody = $this->getSmsBody('header');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Header preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $className = $this->parserSubPath.$this->references['header'];
        $smsObject = new $className($this->data);

        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Header parsing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function prepareBody(): void {
        try {
            $smsBody = $this->getSmsBody('body');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage(), $exception->getCode(), $exception);
        }
        $className = $this->parserSubPath.$this->references['body'];
        $smsObject = new $className($this->data);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
            $this->bodyParserReference = $this->references['body'];
            $this->bodyParserValues = $smsObject->variableValues();
        } catch (\Exception $exception) {
            Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function prepareFooter(): void {
        try {
            $smsBody = $this->getSmsBody('footer');
            $this->body .= ' '.$smsBody->body;
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Footer preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    private function getSmsBody(string $reference): mixed {
        try {
            $smsBody = $this->smsBodyService->getSmsBodyByReference($this->references[$reference]);
        } catch (ModelNotFoundException) {
            throw new MissingSmsReferencesException($reference.' SMS body record not found in database');
        }

        return $smsBody;
    }

    public function validateReferences(): void {
        if (($this->data instanceof Transaction) || ($this->data instanceof ApplianceRate)) {
            $nullSmsBodies = $this->smsBodyService->getNullBodies();
            if (count($nullSmsBodies) > 0) {
                Log::critical('Send sms rejected, some of sms bodies are null', ['Sms Bodies' => $nullSmsBodies]);
                throw new MissingSmsReferencesException('Send sms rejected, some of sms bodies are null');
            }
        }
        if (array_key_exists('header', $this->references)) {
            $this->prepareHeader();
        }
        if (array_key_exists('body', $this->references)) {
            $this->prepareBody();
        }
        if (array_key_exists('footer', $this->references)) {
            $this->prepareFooter();
        }
    }

    public function getReceiver(): string {
        if ($this->data instanceof Transaction) {
            $this->receiver = phone($this->data->sender)->formatE164();
        } elseif ($this->data instanceof ApplianceRate) {
            $this->receiver = $this->data->appliancePerson->person->addresses->first()->phone;
        } elseif (!is_array($this->data) && $this->data->mpmPerson) {
            $this->receiver = $this->data->mpmPerson->addresses[0]->phone;
        } else {
            $this->receiver = phone($this->data['phone'])->formatE164();
        }

        return $this->receiver;
    }

    /**
     * Optional model to associate the created Sms with (e.g. for deduplication).
     * Override in senders that trigger per-entity SMS (e.g. TransactionConfirmation).
     */
    public function getTriggerModel(): ?Model {
        return null;
    }
}
