<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsConfigsCore;
use Inensus\SteamaMeter\Sms\SteamaSmsTypes;

class SteamaSmsConfig extends SmsConfigsCore {
    public array $smsTypes = [
        SteamaSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => 'Inensus\SteamaMeter\Sms\Senders\LowBalanceLimitNotifier',
        SteamaSmsTypes::BALANCE_FEEDBACK => 'Inensus\SteamaMeter\Sms\Senders\BalanceFeedback',
    ];
    public string $bodyParsersPath = 'Inensus\\SteamaMeter\\Sms\\BodyParsers\\';
    public string $servicePath = 'Inensus\SteamaMeter\Services\SteamaSmsBodyService';
}
