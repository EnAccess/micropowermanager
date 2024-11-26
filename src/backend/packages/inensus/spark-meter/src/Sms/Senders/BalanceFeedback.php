<?php

namespace Inensus\SparkMeter\Sms\Senders;

use App\Sms\Senders\SmsSender;

class BalanceFeedback extends SmsSender {
    protected $references = [
        'header' => 'SparkSmsBalanceFeedbackHeader',
        'body' => 'SparkSmsBalanceFeedbackBody',
        'footer' => 'SparkSmsBalanceFeedbackFooter',
    ];
}
