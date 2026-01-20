<?php

namespace App\Plugins\ViberMessaging;

use App\Models\Sms;
use App\Plugins\ViberMessaging\Exceptions\MessageNotSentException;
use App\Plugins\ViberMessaging\Services\ViberCredentialService;
use App\Plugins\ViberMessaging\Services\ViberMessageService;
use Illuminate\Support\Facades\Log;
use Viber\Api\Message\Text;
use Viber\Api\Sender;
use Viber\Bot;

class ViberGateway {
    private Bot $bot;
    private Sender $botSender;

    public function __construct(
        private ViberCredentialService $credentialService,
        private ViberMessageService $viberMessageService,
    ) {
        $credential = $this->credentialService->getCredentials();
        $apiKey = $credential->api_token;
        $this->botSender = new Sender([
            'name' => 'MicroPowerManager',
            'avatar' => 'https://micropowermanager.com/assets/images/Icon_2_5Icon_2_2.png',
        ]);
        $this->bot = new Bot(['token' => $apiKey]);
    }

    public function sendSms(
        string $body,
        string $viberId,
        ?Sms $registeredSms = null,
    ): void {
        try {
            $this->bot->getClient()->sendMessage(
                (new Text())
                    ->setSender($this->botSender)
                    ->setReceiver($viberId)
                    ->setText($body)
            );
            Log::info('Message sent to viber id: '.$viberId);
        } catch (\Exception $exception) {
            Log::error('Viber message sending failed', ['message' => $exception->getMessage()]);

            throw new MessageNotSentException('Viber message sending failed');
        }

        if ($registeredSms instanceof Sms) {
            $this->viberMessageService->create(['sms_id' => $registeredSms->id]);
        }
    }
}
