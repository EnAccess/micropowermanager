<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class BalanceFeedback extends SmsSender {
    /** @var array<string, string>|null */
    protected ?array $references = [
        'header' => 'SparkSmsBalanceFeedbackHeader',
        'body' => 'SparkSmsBalanceFeedbackBody',
        'footer' => 'SparkSmsBalanceFeedbackFooter',
    ];
}
