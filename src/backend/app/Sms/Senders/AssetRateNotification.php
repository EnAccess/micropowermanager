<?php

namespace App\Sms\Senders;

class AssetRateNotification extends SmsSender {
    protected array|null $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'AssetRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
