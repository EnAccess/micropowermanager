<?php

declare(strict_types=1);

namespace App\Plugins\TextbeeSmsGateway\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Events\SmsStoredEvent;
use App\Models\Address\Address;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\TextbeeSmsGateway\Services\TextbeeSmsPollingService;
use App\Services\AddressesService;
use App\Services\SmsService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;

class FetchIncomingSms extends AbstractSharedCommand {
    use ScheduledPluginCommand;

    public const MPM_PLUGIN_ID = MpmPlugin::TEXTBEE_SMS_GATEWAY;

    protected $signature = 'textbee-sms-gateway:fetch-incoming-sms';
    protected $description = 'Poll TextBee gateway for incoming SMS';

    public function __construct(
        private TextbeeSmsPollingService $pollingService,
        private SmsService $smsService,
        private AddressesService $addressesService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# TextBee SMS Polling #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('fetch-incoming-sms command started at '.$startedAt);

        $messages = $this->pollingService->fetchNewMessages();

        foreach ($messages as $message) {
            $phoneNumber = $message['sender'];
            $body = $message['body'];

            $address = $this->addressesService->getAddressByPhoneNumber(str_replace(' ', '', $phoneNumber));
            $sender = $address instanceof Address ? $address->owner : null;
            $senderId = $sender?->getKey();

            $sms = $this->smsService->createSms([
                'receiver' => $address->phone ?? $phoneNumber,
                'body' => $body,
                'sender_id' => $senderId,
                'direction' => Sms::DIRECTION_INCOMING,
                'status' => Sms::STATUS_DELIVERED,
            ]);

            event(new SmsStoredEvent($phoneNumber, $body, $sms));
        }

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Processed: '.count($messages).' messages');
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
