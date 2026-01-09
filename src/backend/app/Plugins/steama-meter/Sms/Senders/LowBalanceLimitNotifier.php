<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class LowBalanceLimitNotifier extends SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = [
        'header' => 'SteamaSmsLowBalanceHeader',
        'body' => 'SteamaSmsLowBalanceBody',
        'footer' => 'SteamaSmsLowBalanceFooter',
    ];
}
