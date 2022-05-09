<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MeterFactory;
use Database\Factories\MeterParameterFactory;
use Database\Factories\MeterTariffFactory;
use Database\Factories\MeterTokenFactory;
use Database\Factories\MeterTypeFactory;
use Database\Factories\PaymentHistoryFactory;
use Database\Factories\PersonFactory;
use Database\Factories\SubConnectionTypeFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

trait CreateEnvironments
{
    private $user, $company, $city, $connectionType, $manufacturer, $meterType, $meter, $meterParameter,
        $meterTariff, $person, $token, $transaction, $connectonTypeIds = [], $subConnectonTypeIds = [];

    protected function createTestData($connectionTypeCount = 2, $subConnectionTypeCount = 2)
    {
        $this->user = UserFactory::new()->create();
        $this->city = CityFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();
        $this->manufacturer = ManufacturerFactory::new()->create();
        $this->meterType = MeterTypeFactory::new()->create();
        $this->meterTariff = MeterTariffFactory::new()->create();
        $this->connectionType = ConnectionTypeFactory::new()->create();
        $this->connectionGroup = ConnectionTypeFactory::new()->create();
        $this->person = PersonFactory::new()->create();

        while ($connectionTypeCount > 0) {
            $connectionType = ConnectionTypeFactory::new()->create();
            array_push($this->connectonTypeIds, $connectionType->id);
            $connectionTypeCount--;
            while ($subConnectionTypeCount > 0) {
                $subConnectionType =
                    SubConnectionTypeFactory::new()->create([
                        'connection_type_id' => $connectionType->id,
                        'tariff_id' => $this->meterTariff->id
                    ]);
                array_push($this->subConnectonTypeIds, $subConnectionType->id);
                $subConnectionTypeCount--;
            }
        }
    }

    protected function getMeter(): mixed
    {
        $this->createTestData();
        $meter = MeterFactory::new()->create([
            'meter_type_id' => $this->meterType->id,
            'in_use' => true,
            'manufacturer_id' => $this->manufacturer->id,
            'serial_number' => str_random(36),
        ]);

        $meterParameter = MeterParameterFactory::new()->create([
            'owner_type' => 'person',
            'owner_id' => $this->person->id,
            'meter_id' => $meter->id,
            'tariff_id' => $this->meterTariff->id,
            'connection_type_id' => $this->connectionType->id,
            'connection_group_id' => $this->connectionGroup->id,
        ]);
        return $meter;
    }

    protected function createMeterWithGeo(): void
    {
        $this->createTestData();
        $meterCunt = 2;
        while ($meterCunt > 0) {
            $meter = MeterFactory::new()->create([
                'meter_type_id' => $this->meterType->id,
                'in_use' => true,
                'manufacturer_id' => 1,
                'serial_number' => str_random(36),
            ]);
            $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
            $this->person = PersonFactory::new()->create();
            $addressData = [
                'city_id' => $this->city->id,
                'geo_id' => $geographicalInformation->id,
            ];

            $meterParameter = MeterParameterFactory::new()->create([
                'owner_type' => 'person',
                'owner_id' => $this->person->id,
                'meter_id' => $meter->id,
                'tariff_id' => $this->meterTariff->id,
                'connection_type_id' => $this->connectionType->id,
                'connection_group_id' => $this->connectionGroup->id,
            ]);
            $address = Address::query()->make([
                'email' => isset($addressData['email']) ?: null,
                'phone' => isset($addressData['phone']) ?: null,
                'street' => isset($addressData['street']) ?: null,
                'city_id' => isset($addressData['city_id']) ?: null,
                'geo_id' => isset($addressData['geo_id']) ?: null,
                'is_primary' => isset($addressData['is_primary']) ?: 0,
            ]);
            $address->owner()->associate($meterParameter)->save();
            $geographicalInformation->owner()->associate($meterParameter)->save();
            $meterCunt--;
        }
    }


}