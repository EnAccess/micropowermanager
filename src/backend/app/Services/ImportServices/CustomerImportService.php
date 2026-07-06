<?php

namespace App\Services\ImportServices;

use App\Models\Address\Address;
use App\Models\City;
use App\Models\Device;
use App\Models\Person\Person;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @extends AbstractImportService<CustomerImportItem>
 */
class CustomerImportService extends AbstractImportService {
    /**
     * @param list<CustomerImportItem> $data
     */
    public function import(array $data): ImportResult {
        $imported = [];
        $failed = [];

        DB::connection('tenant')->beginTransaction();

        try {
            foreach ($data as $item) {
                try {
                    $result = $this->importCustomer($item);
                    if ($result['success']) {
                        $imported[] = $result['customer'];
                    } else {
                        $failed[] = [
                            'name' => trim($item->name.' '.$item->surname),
                            'errors' => $result['errors'],
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Error importing customer', [
                        'name' => $item->name,
                        'error' => $e->getMessage(),
                    ]);
                    $failed[] = [
                        'name' => $item->name,
                        'errors' => ['import' => $e->getMessage()],
                    ];
                }
            }

            DB::connection('tenant')->commit();

            $allFailed = count($imported) === 0 && count($failed) > 0;
            $partitioned = $this->partitionResults($imported);

            return new ImportResult(
                message: $allFailed ? 'All customer imports failed' : 'Customers imported successfully',
                added: $partitioned['added'],
                modified: $partitioned['modified'],
                failed: $failed,
            );
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            $this->throwTransactionFailure('customers', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function importCustomer(CustomerImportItem $item): array {
        // Find or create person by name + surname
        $person = Person::query()
            ->where('name', $item->name)
            ->where('surname', $item->surname)
            ->first();

        $personFields = [
            'name' => $item->name,
            'surname' => $item->surname,
            'is_customer' => 1,
            'type' => 'customer',
        ];

        if ($item->title !== null) {
            $personFields['title'] = $item->title;
        }
        if ($item->birthDate !== null) {
            $personFields['birth_date'] = $item->birthDate;
        }
        if ($item->gender !== null) {
            $personFields['gender'] = $item->gender;
        }

        $isNew = $person === null;

        if ($isNew) {
            $person = Person::query()->create($personFields);
        } else {
            $person->update($personFields);
        }

        // Resolve city by name and create/update primary address
        $cityId = null;
        if ($item->city !== null && $item->city !== '') {
            $city = City::query()->where('name', $item->city)->first();
            if ($city !== null) {
                $cityId = $city->id;
            }
        }

        // Check phone uniqueness
        if ($item->phone !== null && $item->phone !== '') {
            $phoneInUse = Address::query()
                ->where('phone', $item->phone)
                ->where(function ($query) use ($person): void {
                    $query->where('owner_id', '!=', $person->id)
                        ->orWhere('owner_type', '!=', Person::class);
                })
                ->exists();

            if ($phoneInUse) {
                return [
                    'success' => false,
                    'errors' => ['phone' => "Phone number '{$item->phone}' is already assigned to another customer"],
                ];
            }
        }

        $existingAddress = $person->addresses()->where('is_primary', 1)->first();

        $addressFields = [
            'email' => $item->email,
            'phone' => $item->phone,
            'street' => $item->street,
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
        if ($item->devices !== null && $item->devices !== '') {
            $serialNumbers = array_map(trim(...), explode(',', $item->devices));
            Device::query()
                ->whereIn('device_serial', $serialNumbers)
                ->update(['person_id' => $person->id]);
        }

        return [
            'success' => true,
            'action' => $isNew ? 'added' : 'modified',
            'customer' => [
                'id' => $person->id,
                'name' => $person->name,
                'surname' => $person->surname,
                'action' => $isNew ? 'added' : 'modified',
            ],
        ];
    }
}
