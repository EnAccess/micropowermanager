<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Models\Address\Address;
use App\Models\AssetRate;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Plugins;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Services\PluginsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Services\ViberContactService;
use Webpatser\Uuid\Uuid;

abstract class SmsSender
{
    protected $smsBodyService;
    protected $data;
    protected $references;
    public $body = '';
    protected $receiver;
    protected $callback;
    protected $parserSubPath;
    private $smsAndroidSettings;

    public function __construct($data, $smsBodyService, $parserSubPath, $smsAndroidSettings)
    {
        $this->smsBodyService = $smsBodyService;
        $this->data = $data;
        $this->parserSubPath = $parserSubPath;
        $this->smsAndroidSettings = $smsAndroidSettings;
    }

    public function sendSms()
    {
        $this->validateReferences();
        if (config('app.debug')) {
            Log::debug(
                'Send sms on debug is not allowed in debug mode',
                ['number' => $this->receiver, 'message' => $this->body]
            );
            return;
        }
        if (($this->data instanceof Transaction) || ($this->data instanceof AssetRate)) {
            $nullSmsBodies = $this->smsBodyService->getNullBodies();
            if (count($nullSmsBodies)) {
                Log::critical('Send sms rejected, some of sms bodies are null', ['Sms Bodies' => $nullSmsBodies]);
                return;
            }
        }

        $viberId = $this->checkForViberIdOfReceiverIfPluginIsActive();

        if ($viberId) {

            resolve('ViberGateway')
                ->sendSms(
                    $this->body,
                    $viberId,
                    Sms::query()->where('receiver', $this->receiver)->where(
                        'body',
                        $this->body
                    )->latest()->first()
                );
        } else {
            //add sms to sms_gateway
            resolve('SmsProvider')
                ->sendSms(
                    $this->receiver,
                    $this->body,
                    $this->callback,
                    $this->smsAndroidSettings
                );
        }

    }

    public function prepareHeader()
    {
        try {
            $smsBody = $this->getSmsBody('header');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Header preparing failed.', ['message : ' => $exception->getMessage()]);
            return;
        }
        $className = $this->parserSubPath . $this->references['header'];
        $smsObject = new  $className($this->data);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Header parsing failed.', ['message : ' => $exception->getMessage()]);
            return;
        }
    }

    public function prepareBody()
    {
        try {
            $smsBody = $this->getSmsBody('body');
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);
            return;
        }
        $className = $this->parserSubPath . $this->references['body'];
        $smsObject = new  $className($this->data);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);
            return;
        }
    }

    public function prepareFooter()
    {
        try {
            $smsBody = $this->getSmsBody('footer');
            $this->body .= ' ' . $smsBody->body;
        } catch (MissingSmsReferencesException $exception) {
            Log::error('SMS Footer preparing failed.', ['message : ' => $exception->getMessage()]);
            return;
        }
    }

    private function getSmsBody($reference)
    {
        try {
            $smsBody = $this->smsBodyService->getSmsBodyByReference($this->references[$reference]);
        } catch (ModelNotFoundException $e) {
            throw new MissingSmsReferencesException($reference . ' SMS body record not found in database');
        }
        return $smsBody;
    }

    private function validateReferences()
    {
        if (array_key_exists('header', $this->references)) {
            try {
                $this->prepareHeader();
            } catch (MissingSmsReferencesException $exception) {
                Log::critical($exception->getMessage());
            }
        }
        if (array_key_exists('body', $this->references)) {
            try {
                $this->prepareBody();
            } catch (MissingSmsReferencesException $exception) {
                Log::critical($exception->getMessage());
            }
        }
        if (array_key_exists('footer', $this->references)) {
            try {
                $this->prepareFooter();
            } catch (MissingSmsReferencesException $exception) {
                Log::critical($exception->getMessage());
            }
        }
    }

    public function getReceiver()
    {
        if ($this->data instanceof Transaction) {
            $this->receiver = strpos($this->data->sender, '+') === 0 ? $this->data->sender : '+' . $this->data->sender;
        } elseif ($this->data instanceof AssetRate) {
            $this->receiver = strpos(
                $this->data->assetPerson->person->addresses->first()->phone,
                '+'
            ) === 0 ? $this->data->assetPerson->person->addresses->first()->phone
                : '+' . $this->data->assetPerson->person->addresses->first()->phone;
        } elseif (!is_array($this->data) && $this->data->mpmPerson) {
            $this->receiver = strpos(
                $this->data->mpmPerson->addresses[0]->phone,
                '+'
            ) === 0 ? $this->data->mpmPerson->addresses[0]->phone : '+' . $this->data->mpmPerson->addresses[0]->phone;
        } else {
            $this->receiver = strpos(
                $this->data['phone'],
                '+'
            ) === 0 ? $this->data['phone'] : '+' . $this->data['phone'];
        }
        return $this->receiver;
    }

    public function generateCallback($callback)
    {
        $uuid = (string)Uuid::generate(4);
        if (!($this->data instanceof Transaction) && ($this->data instanceof AssetRate)) {
            $this->callback = 'manual';
        }
        $this->callback = sprintf($callback, $uuid);
        return $uuid;
    }

    private function checkForViberIdOfReceiverIfPluginIsActive()
    {
        $pluginsService = app()->make(PluginsService::class);
        $viberContactService = app()->make(ViberContactService::class);
        $viberMessagingPlugin = $pluginsService->getByMpmPluginId(MpmPlugin::VIBER_MESSAGING);

        if ($viberMessagingPlugin && $viberMessagingPlugin->status === Plugins::ACTIVE) {
            $viberContact = $viberContactService->getByReceiverPhoneNumber($this->receiver);

            if (!$viberContact) {
                return null;
            }

            return $viberContact->viber_id;
        }
        return null;
    }
}
