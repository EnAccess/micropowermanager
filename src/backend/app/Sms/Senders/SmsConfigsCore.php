<?php

namespace App\Sms\Senders;

use App\Sms\SmsTypes;

abstract class SmsConfigsCore {
    public array $smsTypes = [
        SmsTypes::TRANSACTION_CONFIRMATION => 'App\Sms\Senders\TransactionConfirmation',
        SmsTypes::APPLIANCE_RATE => 'App\Sms\Senders\AssetRateNotification',
        SmsTypes::OVER_DUE_APPLIANCE_RATE => 'App\Sms\Senders\OverDueAssetRateNotification',
        SmsTypes::MANUAL_SMS => 'App\Sms\Senders\ManualSms',
        SmsTypes::RESEND_INFORMATION => 'App\Sms\Senders\ResendInformationNotification',
    ];
    public string $bodyParsersPath = 'App\\Sms\\BodyParsers\\';
    public string $servicePath = 'App\Services\SmsBodyService';
}
