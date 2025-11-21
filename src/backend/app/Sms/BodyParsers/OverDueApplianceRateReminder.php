<?php

namespace App\Sms\BodyParsers;

use App\Models\ApplianceRate;

class OverDueApplianceRateReminder extends SmsBodyParser {
    public $variables = ['appliance_type_name', 'remaining', 'due_date'];

    public function __construct(protected ApplianceRate $reminderData) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'appliance_type_name' => $this->reminderData->appliancePerson->appliance->applianceType->name,
            'remaining' => $this->reminderData->remaining,
            'due_date' => $this->reminderData->due_date,
            default => $variable,
        };
    }
}
