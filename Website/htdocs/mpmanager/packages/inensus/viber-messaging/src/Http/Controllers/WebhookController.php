<?php

namespace Inensus\ViberMessaging\Http\Controllers;

use App\Models\Meter\Meter;
use App\Services\CompanyService;
use App\Services\DatabaseProxyService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Services\ViberContactService;
use Inensus\ViberMessaging\Services\ViberCredentialService;
use Inensus\ViberMessaging\Services\WebhookService;
use MPM\DatabaseProxy\DatabaseProxyManagerService;
use Viber\Bot;
use Viber\Api\Sender;

class WebhookController extends Controller
{
    private $bot;
    private $botSender;

    public function __construct(
        private ViberCredentialService $credentialService,
        private ViberContactService $viberContactService
    ) {
        $this->botSender = new Sender([
            'name' => 'MicroPowerManager',
            'avatar' => 'https://micropowermanager.com/assets/images/Icon_2_5Icon_2_2.png',
        ]);
    }

    public function index(string $slug)
    {
        Log::info("Webhook called");

        $credential = $this->credentialService->getCredentials();
        $apiKey = $credential->api_token;
        $this->bot = new Bot(['token' => $apiKey]);
        $bot = $this->bot;
        $botSender = $this->botSender;

        $this->bot
            ->onConversation(function ($event) use ($bot, $botSender) {
                return (new \Viber\Api\Message\Text())->setSender($this->botSender)->setText("Can I help you?");
            })
            ->onText('|register+.*|si', function ($event) use ($bot, $botSender) {
                $message = $event->getMessage()->getText();

                try {
                    $message = explode('+', $message);
                    $meterSerialNumber = $message[1];
                } catch (\Exception $e) {
                    $this->answerToCustomer($bot, $botSender, $event, $this->setWrongFormatMessage());

                    return;
                }

                $meter = Meter::query()->where('serial_number', $meterSerialNumber)->first();

                if (!$meter) {
                    $this->answerToCustomer($bot, $botSender, $event, $this->setMeterNotFoundMessage());

                    return;
                }

                $viberContact = $this->viberContactService->getByRegisteredMeterSerialNumber($meterSerialNumber);

                if ($viberContact) {
                    $this->answerToCustomer($bot, $botSender, $event, $this->setAlreadyRegisteredMessage($meterSerialNumber));

                    return;
                }

                $person = $meter->meterParameter->owner;

                if ($person) {
                    $data = ['person_id' => $person->id, 'viber_id' => $event->getSender()->getId()];
                    $this->viberContactService->create($data);
                    $this->answerToCustomer($bot, $botSender, $event, $this->setSuccessMessage());
                } else {
                    Log::info("Someone who is not a customer tried to register with viber");
                }
            })
            ->run();

        Log::info("Webhook is working incoming data :", request()->all());

        return response()->json(['success' => 'success'], 200);
    }

    private function setWrongFormatMessage(): string
    {
        return "Please enter your meter serial number after register+";
    }

    private function setMeterNotFoundMessage(): string
    {
        return "We couldn't find your meter. Please check your meter serial number and try again.";
    }

    private function setSuccessMessage(): string
    {
        return "You have successfully registered with MicroPowerManager.";
    }

    private function setAlreadyRegisteredMessage($meterSerialNumber)
    {
        return "$meterSerialNumber has already registered with MicroPowerManager.";
    }

    private function answerToCustomer($bot, $botSender, $event, $message)
    {
        $bot->getClient()->sendMessage(
            (new \Viber\Api\Message\Text())
                ->setSender($botSender)
                ->setReceiver($event->getSender()->getId())
                ->setText("Hello, {$event->getSender()->getName()}! {$message}")
        );
    }
}