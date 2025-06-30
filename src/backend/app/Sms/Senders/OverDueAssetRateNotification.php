<?php

namespace App\Sms\Senders;

class OverDueAssetRateNotification extends SmsSender {
    protected array|null $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'OverDueAssetRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
