<?php

namespace App\Sms\BodyParsers;

use App\Models\Person\Person;

class SmsResendInformationHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];

    /**
     * @param array<string, mixed>|object $data
     */
    public function __construct(protected array|object $data) {}

    protected function getVariableValue(string $variable): mixed {
        if (!is_array($this->data)) {
            $person = $this->data->device->person ?? null;
        } else {
            try {
                $person = Person::query()
                    ->with(['devices.device' => fn ($q) => $q->where('serial_number', $this->data['meter'])->first()])
                    ->firstOrFail();
            } catch (\Exception) {
                return '';
            }
        }

        return match ($variable) {
            'name' => $person->name ?? '',
            'surname' => $person->surname ?? '',
            default => '',
        };
    }
}
