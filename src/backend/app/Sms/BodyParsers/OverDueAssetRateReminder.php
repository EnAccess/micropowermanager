<?php

namespace App\Sms\BodyParsers;

use App\Models\AssetRate;

class OverDueAssetRateReminder extends SmsBodyParser {
    public $variables = ['appliance_type_name', 'remaining', 'due_date'];

    public function __construct(protected AssetRate $reminderData) {}

    protected function getVariableValue(string $variable): mixed {
        return match ($variable) {
            'appliance_type_name' => $this->reminderData->assetPerson->asset->assetType->name,
            'remaining' => $this->reminderData->remaining,
            'due_date' => $this->reminderData->due_date,
            default => $variable,
        };
    }
}
