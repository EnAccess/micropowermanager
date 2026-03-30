<?php

namespace App\Sms\Senders;

class ApplianceRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'ApplianceRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
