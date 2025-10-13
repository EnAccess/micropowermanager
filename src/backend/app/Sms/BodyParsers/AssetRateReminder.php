<?php

namespace App\Sms\BodyParsers;

class AssetRateReminder extends SmsBodyParser {
    protected $variables = ['appliance_type_name', 'remaining', 'due_date'];

    public function __construct(protected mixed $reminderData) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'appliance_type_name' => $this->reminderData->assetPerson->assetType->name,
            'remaining' => $this->reminderData->remaining,
            'due_date' => $this->reminderData->due_date,
            default => $variable,
        };
    }
}
