<?php

namespace App\Sms\Senders;

class OverDueAssetRateNotification extends SmsSender
{
    protected $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'OverDueAssetRateReminder',
        'footer' => 'SmsReminderFooter'
    ];
}
