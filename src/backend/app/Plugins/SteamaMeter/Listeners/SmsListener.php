<?php

namespace App\Plugins\SteamaMeter\Listeners;

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
        $smsFeedbackWords = $this->smsFeedbackWordService->getSmsFeedbackWords();

        $meterBalance = strpos(strtolower($message), strtolower($smsFeedbackWords[0]->meter_balance));
        if ($meterBalance !== false) {
            $this->smsService->sendSms(
                $steamaCustomer->toArray(),
                SteamaSmsTypes::BALANCE_FEEDBACK,
                SteamaSmsConfig::class
            );

            return;
        }
    }

    public function handle(string $sender, string $message): void {
        // TODO: Uncomment this when steamaco-meter package is refactored with device->meter approach
        // $this->onSmsStored($sender, $message);
    }
}
