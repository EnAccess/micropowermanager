<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class LowBalanceLimitNotifier extends SmsSender {
    protected $references = [
        'header' => 'SparkSmsLowBalanceHeader',
        'body' => 'SparkSmsLowBalanceBody',
        'footer' => 'SparkSmsLowBalanceFooter',
    ];
}
