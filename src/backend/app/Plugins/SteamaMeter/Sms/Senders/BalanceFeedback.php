<?php

namespace App\Plugins\SteamaMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class BalanceFeedback extends SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = [
        'header' => 'SteamaSmsBalanceFeedbackHeader',
        'body' => 'SteamaSmsBalanceFeedbackBody',
        'footer' => 'SteamaSmsBalanceFeedbackFooter',
    ];
}
