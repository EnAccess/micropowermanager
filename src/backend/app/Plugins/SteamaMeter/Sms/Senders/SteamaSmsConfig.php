<?php

namespace App\Plugins\SteamaMeter\Sms\Senders;

use App\Plugins\SteamaMeter\Services\SteamaSmsBodyService;
use App\Plugins\SteamaMeter\Sms\SteamaSmsTypes;
use App\Sms\Senders\SmsConfigsCore;

class SteamaSmsConfig extends SmsConfigsCore {
    public array $smsTypes = [
        SteamaSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => LowBalanceLimitNotifier::class,
        SteamaSmsTypes::BALANCE_FEEDBACK => BalanceFeedback::class,
    ];
    public string $bodyParsersPath = 'Inensus\\SteamaMeter\\Sms\\BodyParsers\\';
    public string $servicePath = SteamaSmsBodyService::class;
}
