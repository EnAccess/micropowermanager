<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class LowBalanceLimitNotifier extends SmsSender {
    protected $references = [
        'header' => 'SteamaSmsLowBalanceHeader',
        'body' => 'SteamaSmsLowBalanceBody',
        'footer' => 'SteamaSmsLowBalanceFooter',
    ];
}
