<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsConfigsCore;
use Inensus\SparkMeter\Services\SmSmsBodyService;
use Inensus\SparkMeter\Sms\SparkSmsTypes;

class SparkSmsConfig extends SmsConfigsCore {
    /**
     * @var array<int, string>
     */
    public array $smsTypes = [
        SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => LowBalanceLimitNotifier::class,
        SparkSmsTypes::BALANCE_FEEDBACK => BalanceFeedback::class,
        SparkSmsTypes::METER_RESET_FEEDBACK => MeterResetFeedback::class,
    ];
    public string $bodyParsersPath = 'Inensus\\SparkMeter\\Sms\\BodyParsers\\';
    public string $servicePath = SmSmsBodyService::class;
}
