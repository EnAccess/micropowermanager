<?php

namespace App\Plugins\BulkRegistration\Services;

use App\Models\Address\Address;
use App\Models\Person\Person;

class AddressService extends CreatorService {
    public function __construct(Address $address) {
        parent::__construct($address);
    }

    public function createRelatedDataIfDoesNotExists($addresses): void {
        foreach ($addresses as $address) {
            Address::query()->firstOrCreate($address, $address);
        }
    }

    /**
     * @param array<string, mixed> $csvData
     */
    public function resolveCsvDataFromComingRow(array $csvData): void {
        $addressConfig = config('bulk-registration.csv_fields.address');
        $returnAddresses = [];
        $firstAddressData = [
            'owner_type' => 'person',
            'owner_id' => $csvData[$addressConfig['person_id']],
            'city_id' => $csvData[$addressConfig['city_id']],
            'phone' => $csvData[$addressConfig['phone']],
            'is_primary' => 1,
        ];
        $returnAddresses[] = $firstAddressData;
        if (array_key_exists((string) $csvData[$addressConfig['alternative_phone']], $csvData)) {
            $alternativeAddress = [
                'owner_type' => 'person',
                'owner_id' => $csvData[$addressConfig['person_id']],
                'city_id' => $csvData[$addressConfig['city_id']],
                'phone' => $csvData[$addressConfig['alternative_phone']],
                'is_primary' => 0,
            ];
            $returnAddresses[] = $alternativeAddress;
        }
        $this->createRelatedDataIfDoesNotExists($returnAddresses);
    }

    public function createForPerson(Person $person, int $cityId, ?string $phone, ?string $email, ?string $street, bool $isPrimary): Address {
        $address = new Address();
        $address->owner()->associate($person);
        $address->city_id = $cityId;
        $address->phone = $phone;
        $address->email = $email;
        $address->is_primary = $isPrimary ? 1 : 0;
        $address->street = $street;
        $address->save();

        return $address;
    }
}
