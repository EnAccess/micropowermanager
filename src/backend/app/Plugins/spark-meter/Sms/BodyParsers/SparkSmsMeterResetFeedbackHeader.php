<?php

namespace Inensus\SparkMeter\Sms\BodyParsers;

use App\Models\Person\Person;
use App\Sms\BodyParsers\SmsBodyParser;

class SparkSmsMeterResetFeedbackHeader extends SmsBodyParser {
    protected $variables = ['name', 'surname'];

    public function __construct(
        protected mixed $data,
    ) {}

    protected function getVariableValue(string $variable): mixed {
        if (!is_array($this->data)) {
            $person = $this->data->meter->whereHasMorph('owner', [Person::class])->first()->owner()->first();
        } else {
            try {
                $person = Person::query()->with([
                    'devices.device' => fn ($q) => $q->where('serial_number', $this->data['meter'])->first(),
                ])->firstOrFail();
            } catch (\Exception) {
                return '';
            }
        }

        return match ($variable) {
            'name' => $person->name,
            'surname' => $person->surname,
            default => $variable,
        };
    }
}
