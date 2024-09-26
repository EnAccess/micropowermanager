<?php

namespace App\Sms\BodyParsers;

use App\Models\AssetRate;

class SmsReminderHeader extends SmsBodyParser
{
    protected $variables = ['name', 'surname'];
    protected $reminderData;

    public function __construct(AssetRate $reminderData)
    {
        $this->reminderData = $reminderData;
    }

    protected function getVariableValue($variable)
    {
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
