<?php

namespace Inensus\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class BalanceFeedback extends SmsSender {
    protected $references = [
        'header' => 'SteamaSmsBalanceFeedbackHeader',
        'body' => 'SteamaSmsBalanceFeedbackBody',
        'footer' => 'SteamaSmsBalanceFeedbackFooter',
    ];
}
