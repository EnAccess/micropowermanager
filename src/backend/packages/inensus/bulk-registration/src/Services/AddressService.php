<?php

namespace Inensus\BulkRegistration\Services;

use App\Models\Address\Address;

class AddressService extends CreatorService {
    public function __construct(Address $address) {
        parent::__construct($address);
    }

    public function createRelatedDataIfDoesNotExists($addresses) {
        foreach ($addresses as $address) {
            Address::query()->firstOrCreate($address, $address);
        }
    }

    public function resolveCsvDataFromComingRow($csvData) {
        $addressConfig = config('bulk-registration.csv_fields.address');
        $returnAddresses = [];
        $firstAddressData = [
            'owner_type' => 'person',
            'owner_id' => $csvData[$addressConfig['person_id']],
            'city_id' => $csvData[$addressConfig['city_id']],
            'phone' => $csvData[$addressConfig['phone']],
            'is_primary' => 1,
        ];
        array_push($returnAddresses, $firstAddressData);
        if (array_key_exists($csvData[$addressConfig['alternative_phone']], $csvData)) {
            $alternativeAddress = [
                'owner_type' => 'person',
                'owner_id' => $csvData[$addressConfig['person_id']],
                'city_id' => $csvData[$addressConfig['city_id']],
                'phone' => $csvData[$addressConfig['alternative_phone']],
                'is_primary' => 0,
            ];
            array_push($returnAddresses, $alternativeAddress);
        }
        $this->createRelatedDataIfDoesNotExists($returnAddresses);
    }

    public function createForPerson(int $personId, int $cityId, ?string $phone, ?string $email, ?string $street, bool $isPrimary): Address {
        $address = new Address();
        $address->setOwner($personId, 'person');
        $address->setCityId($cityId);
        $address->setPhone($phone);
        $address->setEmail($email);
        $address->setIsPrimary($isPrimary);
        $address->setStreet($street);
        $address->save();

        return $address;
    }
}
