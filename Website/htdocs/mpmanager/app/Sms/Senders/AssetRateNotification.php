<?php

namespace App\Sms\Senders;

class AssetRateNotification extends SmsSender
{
    protected $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'AssetRateReminder',
        'footer' => 'SmsReminderFooter',
    ];
}
