<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsConfigsCore;
use Inensus\SparkMeter\Sms\SparkSmsTypes;

class SparkSmsConfig extends SmsConfigsCore {
    /**
     * @var array<int, string>
     */
    public array $smsTypes = [
        SparkSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => 'Inensus\SparkMeter\Sms\Senders\LowBalanceLimitNotifier',
        SparkSmsTypes::BALANCE_FEEDBACK => 'Inensus\SparkMeter\Sms\Senders\BalanceFeedback',
        SparkSmsTypes::METER_RESET_FEEDBACK => 'Inensus\SparkMeter\Sms\Senders\MeterResetFeedback',
    ];
    public string $bodyParsersPath = 'Inensus\\SparkMeter\\Sms\\BodyParsers\\';
    public string $servicePath = 'Inensus\SparkMeter\Services\SmSmsBodyService';
}
