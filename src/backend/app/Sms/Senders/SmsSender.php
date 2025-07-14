<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Models\AssetRate;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Services\PluginsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Services\ViberContactService;

abstract class SmsSender {
    public const VIBER_GATEWAY = 'ViberGateway';
    public const AFRICAS_TALKING_GATEWAY = 'AfricasTalkingGateway';
    public const DEFAULT_GATEWAY = 'AndroidGateway';

    protected mixed $smsBodyService;
    protected mixed $data;

    /** @var array<string, string>|null */
    protected ?array $references;
    public string $body = '';
    protected ?string $receiver;
    protected ?string $callback;
    protected string $parserSubPath;
    private mixed $smsAndroidSettings;
    private ?string $viberIdOfReceiver;

    public function __construct(
        mixed $data,
        mixed $smsBodyService,
        string $parserSubPath,
        mixed $smsAndroidSettings,
    ) {
        $this->smsBodyService = $smsBodyService;
        $this->data = $data;
        $this->parserSubPath = $parserSubPath;
        $this->smsAndroidSettings = $smsAndroidSettings;
    }

    public function sendSms(): void {
        $gateway = $this->determineGateway();
        $lastRecordedSMS = Sms::query()
            ->where('receiver', $this->receiver)
            ->orWhere('receiver', ltrim($this->receiver, '+'))
            ->where(
                'body',
                $this->body
            )->latest()->first();

        switch ($gateway) {
            case self::VIBER_GATEWAY:
                resolve(self::VIBER_GATEWAY)
                    ->sendSms(
                        $this->body,
                        $this->viberIdOfReceiver,
                        $lastRecordedSMS
                    );
                break;

            case self::AFRICAS_TALKING_GATEWAY:
                resolve(self::AFRICAS_TALKING_GATEWAY)
                    ->sendSms(
                        $this->body,
                        $this->receiver,
                        $lastRecordedSMS
                    );
                break;

            default:
                resolve(self::DEFAULT_GATEWAY)
                    ->sendSms(
                        $this->receiver,
                        $this->body,
                        $this->callback,
                        $this->smsAndroidSettings
                    );
                break;
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
        } catch (ModelNotFoundException $e) {
            throw new MissingSmsReferencesException($reference.' SMS body record not found in database');
        }

        return $smsBody;
    }

    public function validateReferences(): void {
        if (($this->data instanceof Transaction) || ($this->data instanceof AssetRate)) {
            $nullSmsBodies = $this->smsBodyService->getNullBodies();
            if (count($nullSmsBodies)) {
                Log::critical('Send sms rejected, some of sms bodies are null', ['Sms Bodies' => $nullSmsBodies]);
                throw new MissingSmsReferencesException('Send sms rejected, some of sms bodies are null');
            }
        }
        try {
            if (array_key_exists('header', $this->references)) {
                $this->prepareHeader();
            }
            if (array_key_exists('body', $this->references)) {
                $this->prepareBody();
            }
            if (array_key_exists('footer', $this->references)) {
                $this->prepareFooter();
            }
        } catch (MissingSmsReferencesException $exception) {
            throw $exception;
        }
    }

    public function getReceiver(): string {
        if ($this->data instanceof Transaction) {
            $this->receiver = strpos($this->data->sender, '+') === 0 ? $this->data->sender : '+'.$this->data->sender;
        } elseif ($this->data instanceof AssetRate) {
            $this->receiver = strpos(
                $this->data->assetPerson->person->addresses->first()->phone,
                '+'
            ) === 0 ? $this->data->assetPerson->person->addresses->first()->phone
                : '+'.$this->data->assetPerson->person->addresses->first()->phone;
        } elseif (!is_array($this->data) && $this->data->mpmPerson) {
            $this->receiver = strpos(
                $this->data->mpmPerson->addresses[0]->phone,
                '+'
            ) === 0 ? $this->data->mpmPerson->addresses[0]->phone : '+'.$this->data->mpmPerson->addresses[0]->phone;
        } else {
            $this->receiver = strpos(
                $this->data['phone'],
                '+'
            ) === 0 ? $this->data['phone'] : '+'.$this->data['phone'];
        }

        return $this->receiver;
    }

    public function setCallback(string $callback, string $uuid): void {
        $this->callback = sprintf($callback, $uuid);
    }

    private function determineGateway(): string {
        $pluginsService = app()->make(PluginsService::class);
        $africasTalkingPlugin = $pluginsService->getByMpmPluginId(MpmPlugin::AFRICAS_TALKING);
        $gateway = self::DEFAULT_GATEWAY;

        if ($africasTalkingPlugin && $africasTalkingPlugin->status == Plugins::ACTIVE) {
            $gateway = self::AFRICAS_TALKING_GATEWAY;
        }

        $viberMessagingPlugin = $pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);

        if ($viberMessagingPlugin && $viberMessagingPlugin->status == Plugins::ACTIVE) {
            $viberContactService = app()->make(ViberContactService::class);
            $viberContact = $viberContactService->getByReceiverPhoneNumber($this->receiver);

            if (!$viberContact) {
                return $gateway;
            }

            $this->viberIdOfReceiver = $viberContact->viber_id;
            $gateway = self::VIBER_GATEWAY;
        }

        return $gateway;
    }
}
