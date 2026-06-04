<?php

namespace App\Plugins\SteamaMeter\Listeners;

use App\Events\SmsStoredEvent;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Services\SteamaCustomerService;
use App\Plugins\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use App\Plugins\SteamaMeter\Sms\Senders\SteamaSmsConfig;
use App\Plugins\SteamaMeter\Sms\SteamaSmsTypes;
use App\Services\SmsService;

class SmsListener {
    public function __construct(
        private SteamaSmsFeedbackWordService $smsFeedbackWordService,
        private SteamaCustomerService $customerService,
        private SmsService $smsService,
    ) {}

    public function onSmsStored(string $sender, string $message): void {
        $steamaCustomer = $this->customerService->getSteamaCustomerWithPhone($sender);
        if (!$steamaCustomer instanceof SteamaCustomer) {
            return;
        }
        $feedbackWord = $this->smsFeedbackWordService->getSmsFeedbackWords()->first();
        if (!$feedbackWord || !$feedbackWord->meter_balance) {
            return;
        }

        if (str_contains(strtolower($message), strtolower($feedbackWord->meter_balance))) {
            $this->smsService->sendSms(
                $steamaCustomer,
                SteamaSmsTypes::BALANCE_FEEDBACK,
                SteamaSmsConfig::class
            );
        }
    }

    public function handle(SmsStoredEvent $event): void {
        $this->onSmsStored($event->sender, $event->message);
    }
}
