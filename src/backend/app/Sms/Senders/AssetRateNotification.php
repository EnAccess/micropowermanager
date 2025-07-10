<?php

namespace App\Sms\Senders;

class AssetRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'AssetRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
