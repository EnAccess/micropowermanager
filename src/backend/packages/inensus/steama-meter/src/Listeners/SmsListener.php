<?php

namespace Inensus\SteamaMeter\Listeners;

use App\Services\SmsService;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use Inensus\SteamaMeter\Sms\Senders\SteamaSmsConfig;
use Inensus\SteamaMeter\Sms\SteamaSmsTypes;

class SmsListener {
    public function __construct(
        private SteamaSmsFeedbackWordService $smsFeedbackWordService,
        private SteamaCustomerService $customerService,
        private SmsService $smsService,
    ) {}

    public function onSmsStored($sender, $message) {
        $steamaCustomer = $this->customerService->getSteamaCustomerWithPhone($sender);
        if (!$steamaCustomer) {
            return;
        }
        $smsFeedbackWords = $this->smsFeedbackWordService->getSmsFeedbackWords();

        $meterBalance = strpos(strtolower($message), strtolower($smsFeedbackWords[0]->meter_balance));
        if ($meterBalance !== false) {
            $this->smsService->sendSms(
                $steamaCustomer,
                SteamaSmsTypes::BALANCE_FEEDBACK,
                SteamaSmsConfig::class
            );

            return;
        }
    }

    public function handle($sender, $message) {
        // TODO: Uncomment this when steamaco-meter package is refactored with device->meter approach
        // $this->onSmsStored($sender, $message);
    }
}
