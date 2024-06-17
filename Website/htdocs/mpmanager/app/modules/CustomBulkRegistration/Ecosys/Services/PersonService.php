<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\Person\Person;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class PersonService extends CreatorService
{
    private $phone;

    public function __construct(Person $person)
    {
        parent::__construct($person);
    }

    public function resolveCsvDataFromComingRow($csvData)
    {
        $personConfig = [
            'name' => 'name',
            'gender' => 'gender',
            'age' => 'age',
            'phone' => 'phone',
        ];
        $personData = [
            'name' => $csvData[$personConfig['name']],
            'surname' => '',
            'sex' => strtolower($csvData[$personConfig['gender']]),
            'birth_date' => $this->getBirthDateByGivenAge($csvData[$personConfig['age']]),
        ];

        $this->phone = $csvData[$personConfig['phone']];

        return $this->createRelatedDataIfDoesNotExists($personData);
    }

    private function getBirthDateByGivenAge($age)
    {
        return date('Y-m-d', strtotime('-'.$age.' years'));
    }

    public function createRelatedDataIfDoesNotExists($personData)
    {
        $phone = $this->phone;
        $existingPerson =
            Person::query()
                ->where('birth_date', $personData['birth_date'])
                ->where('sex', $personData['sex'])
                ->whereHas(
                    'addresses',
                    function ($q) use ($phone) {
                        $q->where('phone', $phone);
                    }
                )->first();

        if ($existingPerson) {
            return ['existing' => true, 'person' => $existingPerson];
        }
        $person = Person::query()->create($personData);

        return ['existing' => false, 'person' => $person];
    }
}
