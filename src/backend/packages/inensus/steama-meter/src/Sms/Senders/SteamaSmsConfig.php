<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsConfigsCore;
use Inensus\SteamaMeter\Services\SteamaSmsBodyService;
use Inensus\SteamaMeter\Sms\SteamaSmsTypes;

class SteamaSmsConfig extends SmsConfigsCore {
    public array $smsTypes = [
        SteamaSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => LowBalanceLimitNotifier::class,
        SteamaSmsTypes::BALANCE_FEEDBACK => BalanceFeedback::class,
    ];
    public string $bodyParsersPath = 'Inensus\\SteamaMeter\\Sms\\BodyParsers\\';
    public string $servicePath = SteamaSmsBodyService::class;
}
