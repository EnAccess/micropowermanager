<?php

namespace App\Sms\Senders;

class OverDueApplianceRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'OverDueApplianceRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
