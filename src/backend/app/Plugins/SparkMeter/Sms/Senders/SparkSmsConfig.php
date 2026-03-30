<?php

namespace App\Plugins\SparkMeter\Sms\Senders;

use App\Plugins\SparkMeter\Services\SmSmsBodyService;
use App\Plugins\SparkMeter\Sms\SparkSmsTypes;
use App\Sms\Senders\SmsConfigsCore;

class SparkSmsConfig extends SmsConfigsCore {
    /**
     * @var array<int, string>
     */
    public array $smsTypes = [
        SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => LowBalanceLimitNotifier::class,
        SparkSmsTypes::BALANCE_FEEDBACK => BalanceFeedback::class,
        SparkSmsTypes::METER_RESET_FEEDBACK => MeterResetFeedback::class,
    ];
    public string $bodyParsersPath = 'App\\Plugins\\SparkMeter\\Sms\\BodyParsers\\';
    public string $servicePath = SmSmsBodyService::class;
}
