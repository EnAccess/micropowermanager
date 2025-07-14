<?php

namespace App\Sms\Senders;

class OverDueAssetRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'OverDueAssetRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
