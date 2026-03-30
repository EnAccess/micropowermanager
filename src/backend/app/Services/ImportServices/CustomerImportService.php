<?php

namespace App\Services\ImportServices;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\Device;
use App\Models\Person\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CustomerImportService extends AbstractImportService {
    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function import(array $data): array {
        $importData = $data;
        if (isset($data['data']) && is_array($data['data'])) {
            $importData = $data['data'];
        }

        $errors = $this->validate($importData);
        if ($errors !== []) {
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($importData as $customerData) {
                try {
                    $result = $this->importCustomer($customerData);
                    if ($result['success']) {
                        $imported[] = $result['customer'];
                    } else {
                        $failed[] = [
                            'name' => trim(($customerData['name'] ?? '').' '.($customerData['surname'] ?? '')),
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing customer', [
                        'name' => $customerData['name'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $customerData['name'] ?? 'unknown',
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;

            return [
                'success' => !$allFailed,
                'message' => $allFailed ? 'All customer imports failed' : 'Customers imported successfully',
                'imported_count' => count($imported),
                'failed_count' => count($failed),
                'imported' => $imported,
                'failed' => $failed,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error during customer import transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'errors' => ['transaction' => 'Failed to import customers: '.$e->getMessage()],
            ];
        }
    }

    /**
     * @param array<string, mixed> $customerData
     *
     * @return array<string, mixed>
     */
    private function importCustomer(array $customerData): array {
        $name = $customerData['name'];
        $surname = $customerData['surname'] ?? '';

        // Find or create person by name + surname
        $person = Person::query()
            ->where('name', $name)
            ->where('surname', $surname)
            ->first();

        $personFields = [
            'name' => $name,
            'surname' => $surname,
            'is_customer' => 1,
            'type' => 'customer',
        ];

        if (isset($customerData['title'])) {
            $personFields['title'] = $customerData['title'];
        }
        if (isset($customerData['birth_date'])) {
            $personFields['birth_date'] = $customerData['birth_date'];
        }
        if (isset($customerData['gender'])) {
            $personFields['gender'] = $customerData['gender'];
        }

        if ($person === null) {
            $person = Person::query()->create($personFields);
        } else {
            $person->update($personFields);
        }

        // Resolve city by name and create/update primary address
        $cityId = null;
        if (!empty($customerData['city'])) {
            $city = City::query()->where('name', $customerData['city'])->first();
            if ($city !== null) {
                $cityId = $city->id;
            }
        }

        $existingAddress = $person->addresses()->where('is_primary', 1)->first();

        $addressFields = [
            'email' => $customerData['email'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'street' => $customerData['street'] ?? null,
            'city_id' => $cityId,
            'is_primary' => 1,
        ];

        if ($existingAddress !== null) {
            $existingAddress->update($addressFields);
        } else {
            $address = new Address($addressFields);
            $address->owner()->associate($person);
            $address->save();
        }

        // Link devices by serial number
        if (!empty($customerData['devices'])) {
            $serialNumbers = array_map(trim(...), explode(',', $customerData['devices']));
            Device::query()
                ->whereIn('device_serial', $serialNumbers)
                ->update(['person_id' => $person->id]);
        }

        return [
            'success' => true,
            'customer' => [
                'id' => $person->id,
                'name' => $person->name,
                'surname' => $person->surname,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, string>
     */
    public function validate(array $data): array {
        $errors = [];

        foreach ($data as $index => $customerData) {
            if (!is_array($customerData)) {
                $errors["customer_{$index}"] = 'Customer data must be an array';
                continue;
            }

            if (empty($customerData['name'])) {
                $errors["customer_{$index}.name"] = 'Name is required';
            }
        }

        return $errors;
    }
}
