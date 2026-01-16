<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Models\Person\Person;

class PersonService extends CreatorService {
    public function __construct(Person $person) {
        parent::__construct($person);
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData): Person {
        $personConfig = config('bulk-registration.csv_fields.person');
        $personData = [
            'name' => $csvData[$personConfig['name']],
            'surname' => $csvData[$personConfig['surname']],
        ];

        return Person::query()->create($personData);
    }
}
