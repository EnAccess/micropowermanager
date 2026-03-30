<?php

namespace App\Sms\Senders;

use App\Services\SmsBodyService;
use App\Sms\SmsTypes;

abstract class SmsConfigsCore {
    /**
     * @var array<int, string>
     */
    public array $smsTypes = [
        SmsTypes::TRANSACTION_CONFIRMATION => TransactionConfirmation::class,
        SmsTypes::APPLIANCE_RATE => ApplianceRateNotification::class,
        SmsTypes::OVER_DUE_APPLIANCE_RATE => OverDueApplianceRateNotification::class,
        SmsTypes::MANUAL_SMS => ManualSms::class,
        SmsTypes::RESEND_INFORMATION => ResendInformationNotification::class,
    ];
    public string $bodyParsersPath = 'App\\Sms\\BodyParsers\\';
    public string $servicePath = SmsBodyService::class;
}
