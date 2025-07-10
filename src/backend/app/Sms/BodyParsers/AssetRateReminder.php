<?php

namespace App\Sms\BodyParsers;

use App\Models\AssetRate;

class AssetRateReminder extends SmsBodyParser {
    protected $variables = ['appliance_type_name', 'remaining', 'due_date'];
    protected $reminderData;

    public function __construct(AssetRate $reminderData) {
        $this->reminderData = $reminderData;
    }

    protected function getVariableValue($variable): mixed {
        switch ($variable) {
            case 'appliance_type_name':
                $variable = $this->reminderData->assetPerson->assetType->name;
                break;
            case 'remaining':
                $variable = $this->reminderData->remaining;
                break;
            case 'due_date':
                $variable = $this->reminderData->due_date;
                break;
        }

        return $variable;
    }
}
