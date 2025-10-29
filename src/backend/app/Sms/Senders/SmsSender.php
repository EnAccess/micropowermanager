<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Models\AssetRate;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Services\SmsGatewayResolverService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

abstract class SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = null;
    public string $body = '';
    protected ?string $receiver = null;
    protected ?string $callback = null;

    public function __construct(protected mixed $data, protected mixed $smsBodyService, protected string $parserSubPath, private mixed $smsAndroidSettings) {}

    public function sendSms(): void {
        $gatewayResolver = app()->make(SmsGatewayResolverService::class);

        [$gateway, $viberId] = $gatewayResolver->determineGateway($this->receiver);

        $lastRecordedSMS = Sms::query()
            ->where('receiver', $this->receiver)
            ->orWhere('receiver', ltrim($this->receiver, '+'))
            ->where(
                'body',
                $this->body
            )->latest()->first();

        $resolved = $gatewayResolver->resolveGatewayAndArgs($gateway, $lastRecordedSMS, [
            'body' => $this->body,
            'receiver' => $this->receiver,
            'viberId' => $viberId,
            'callback' => $this->callback,
            'smsAndroidSettings' => $this->smsAndroidSettings,
        ]);

        $resolved['gateway']->sendSms(...$resolved['args']);

        if ($gateway !== SmsGatewayResolverService::DEFAULT_GATEWAY) {
            $lastRecordedSMS->gateway_id = $resolved['gatewayId'];
            $lastRecordedSMS->save();
        }
    }

    public function prepareHeader(): void {
        try {
            $smsBody = $this->getSmsBody('header');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Header preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage());
        }

        $className = $this->parserSubPath.$this->references['header'];
        $smsObject = new $className($this->data);

        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Header parsing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage());
        }
    }

    public function prepareBody(): void {
        try {
            $smsBody = $this->getSmsBody('body');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage());
        }
        $className = $this->parserSubPath.$this->references['body'];
        $smsObject = new $className($this->data);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage());
        }
    }

    public function prepareFooter(): void {
        try {
            $smsBody = $this->getSmsBody('footer');
            $this->body .= ' '.$smsBody->body;
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Footer preparing failed.', ['message : ' => $exception->getMessage()]);
            throw new MissingSmsReferencesException($exception->getMessage());
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
        if (($this->data instanceof Transaction) || ($this->data instanceof AssetRate)) {
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
            $this->receiver = str_starts_with($this->data->sender, '+') ? $this->data->sender : '+'.$this->data->sender;
        } elseif ($this->data instanceof AssetRate) {
            $this->receiver = str_starts_with($this->data->assetPerson->person->addresses->first()->phone, '+') ? $this->data->assetPerson->person->addresses->first()->phone
                : '+'.$this->data->assetPerson->person->addresses->first()->phone;
        } elseif (!is_array($this->data) && $this->data->mpmPerson) {
            $this->receiver = str_starts_with($this->data->mpmPerson->addresses[0]->phone, '+') ? $this->data->mpmPerson->addresses[0]->phone : '+'.$this->data->mpmPerson->addresses[0]->phone;
        } else {
            $this->receiver = str_starts_with($this->data['phone'], '+') ? $this->data['phone'] : '+'.$this->data['phone'];
        }

        return $this->receiver;
    }

    public function setCallback(string $callback, string $uuid): void {
        $this->callback = sprintf($callback, $uuid);
    }
}
