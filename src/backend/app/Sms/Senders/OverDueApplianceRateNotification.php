<?php

namespace App\Sms\Senders;

use App\Models\ApplianceRate;
use Illuminate\Database\Eloquent\Model;

class OverDueApplianceRateNotification extends SmsSender {
    protected ?array $references = [
        'header' => 'SmsReminderHeader',
        'body' => 'OverDueApplianceRateReminder',
        'footer' => 'SmsReminderFooter',
    ];

    public function getTriggerModel(): ?Model {
        return $this->data instanceof ApplianceRate ? $this->data : null;
    }
}
