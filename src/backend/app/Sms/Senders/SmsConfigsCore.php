<?php

namespace App\Sms\Senders;

use App\Sms\SmsTypes;

abstract class SmsConfigsCore {
    public $smsTypes = [
        SmsTypes::TRANSACTION_CONFIRMATION => 'App\Sms\Senders\TransactionConfirmation',
        SmsTypes::APPLIANCE_RATE => 'App\Sms\Senders\AssetRateNotification',
        SmsTypes::OVER_DUE_APPLIANCE_RATE => 'App\Sms\Senders\OverDueAssetRateNotification',
        SmsTypes::MANUAL_SMS => 'App\Sms\Senders\ManualSms',
        SmsTypes::RESEND_INFORMATION => 'App\Sms\Senders\ResendInformationNotification',
    ];
    public $bodyParsersPath = 'App\\Sms\\BodyParsers\\';
    public $servicePath = 'App\Services\SmsBodyService';
}
