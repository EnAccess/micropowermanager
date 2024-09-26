<?php

namespace MPM\CustomBulkRegistration\Ecosys\Services;

use App\Models\Address\Address;
use MPM\CustomBulkRegistration\Abstract\CreatorService;

class AddressService extends CreatorService
{
    public function __construct(Address $address)
    {
        parent::__construct($address);
    }

    public function resolveCsvDataFromComingRow($csvData): void
    {
        $addressConfig = [
            'person_id' => 'person_id',
            'city_id' => 'city_id',
            'phone' => 'phone',
            'alternative_phone' => 'alternative_phone',
        ];
        $returnAddresses = [];
        $firstAddressData = [
            'owner_type' => 'person',
            'owner_id' => $csvData[$addressConfig['person_id']],
            'city_id' => $csvData[$addressConfig['city_id']],
            'phone' => $csvData[$addressConfig['phone']],
            'is_primary' => 1,
        ];
        $returnAddresses[] = $firstAddressData;

        if (array_key_exists($csvData[$addressConfig['alternative_phone']], $csvData)) {
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

    public function createRelatedDataIfDoesNotExists($addresses): void
    {
        foreach ($addresses as $address) {
            Address::query()->firstOrCreate($address, $address);
        }
    }
}
