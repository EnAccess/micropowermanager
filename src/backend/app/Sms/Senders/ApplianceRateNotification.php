<?php

namespace App\Sms\Senders;

use App\Models\ApplianceRate;
use Illuminate\Database\Eloquent\Model;

class ApplianceRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'ApplianceRateReminder',
        'footer' => 'SmsReminderFooter',
    ];

    public function getTriggerModel(): ?Model {
        return $this->data instanceof ApplianceRate ? $this->data : null;
    }
}
