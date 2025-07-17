<?php

namespace App\Sms\BodyParsers;

use App\Models\AssetRate;

class SmsReminderHeader extends SmsBodyParser {
    /** @var array<int, string> */
    protected $variables = ['name', 'surname'];

    protected AssetRate $reminderData;

    public function __construct(AssetRate $reminderData) {
        $this->reminderData = $reminderData;
    }

    protected function getVariableValue(string $variable): mixed {
        $person = $this->reminderData->assetPerson->person;
        switch ($variable) {
            case 'name':
                $variable = $person->name;
                break;
            case 'surname':
                $variable = $person->surname;
                break;
        }

        return $variable;
    }
}
