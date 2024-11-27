<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsConfigsCore;
use Inensus\SteamaMeter\Sms\SteamaSmsTypes;

class SteamaSmsConfig extends SmsConfigsCore {
    public $smsTypes = [
        SteamaSmsTypes::LOW_BALANCE_LIMIT_NOTIFIER => 'Inensus\SteamaMeter\Sms\Senders\LowBalanceLimitNotifier',
        SteamaSmsTypes::BALANCE_FEEDBACK => 'Inensus\SteamaMeter\Sms\Senders\BalanceFeedback',
    ];
    public $bodyParsersPath = 'Inensus\\SteamaMeter\\Sms\\BodyParsers\\';
    public $servicePath = 'Inensus\SteamaMeter\Services\SteamaSmsBodyService';
}
