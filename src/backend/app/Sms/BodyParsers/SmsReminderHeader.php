<?php

namespace App\Sms\BodyParsers;

class SmsReminderHeader extends SmsBodyParser {
    /** @var array<int, string> */
    protected $variables = ['name', 'surname'];

    public function __construct(protected mixed $reminderData) {}

    protected function getVariableValue(string $variable): mixed {
        $person = $this->reminderData->assetPerson->person;

        return match ($variable) {
            'name' => $person->name,
            'surname' => $person->surname,
            default => $variable,
        };
    }
}
