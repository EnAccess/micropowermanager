<?php

namespace Inensus\SparkMeter\Listeners;

use App\Models\Meter\Meter;
use App\Services\SmsService;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\SmSmsFeedbackWordService;
use Inensus\SparkMeter\Sms\Senders\SparkSmsConfig;
use Inensus\SparkMeter\Sms\SparkSmsTypes;

class SmsListener {
    private SmSmsFeedbackWordService $smsFeedbackWordService;
    private CustomerService $customerService;
    private SmsService $smsService;

    public function __construct(
        SmSmsFeedbackWordService $smsFeedbackWordService,
        CustomerService $customerService,
        SmsService $smsService,
    ) {
        $this->smsFeedbackWordService = $smsFeedbackWordService;
        $this->customerService = $customerService;
        $this->smsService = $smsService;
    }

    public function onSmsStored($sender, $message): void {
        $sparkCustomer = $this->customerService->getSparkCustomerWithPhone($sender);
        if (!$sparkCustomer) {
            return;
        }
        $smsFeedbackWords = $this->smsFeedbackWordService->getSmsFeedbackWords();
        $meter = $sparkCustomer->mpmPerson->meters[0]->meter;
        $meterReset = strpos(strtolower($message), strtolower($smsFeedbackWords[0]->meter_reset));
        if ($meterReset !== false) {
            try {
                $this->customerService->resetMeter($sparkCustomer);
                $this->smsService->sendSms(
                    $meter,
                    SparkSmsTypes::METER_RESET_FEEDBACK,
                    SparkSmsConfig::class
                );

                return;
            } catch (SparkAPIResponseException $exception) {
                $this->smsService->sendSms(
                    ['meter' => $meter['serial_number'], 'phone' => $sender],
                    SparkSmsTypes::METER_RESET_FEEDBACK,
                    SparkSmsConfig::class
                );
            }
        }
        $meterBalance = strpos(strtolower($message), strtolower($smsFeedbackWords[0]->meter_balance));
        if ($meterBalance !== false) {
            $this->smsService->sendSms(
                $sparkCustomer,
                SparkSmsTypes::BALANCE_FEEDBACK,
                SparkSmsConfig::class
            );

            return;
        }
    }

    public function handle($sender, $message): void {
        // TODO: Uncomment this when spark-meter package is refactored with device->meter approach
        // $this->onSmsStored($sender, $message);
    }
}
