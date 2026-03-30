<?php

namespace App\Plugins\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class LowBalanceLimitNotifier extends SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = [
        'header' => 'SparkSmsLowBalanceHeader',
        'body' => 'SparkSmsLowBalanceBody',
        'footer' => 'SparkSmsLowBalanceFooter',
    ];
}
