<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use Database\Factories\AddressFactory;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionGroupFactory;
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
use Database\Factories\TimeOfUsageFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Database\Eloquent\Collection;
trait CreateEnvironments
{
    use RefreshMultipleDatabases, WithFaker;

    private
        $user,
        $company,
        $city,
        $connectionType,
        $manufacturer,
        $meterType,
        $meter,
        $meterParameter,
        $meterTariff,
        $person,
        $token,
        $transaction,
        $connectionGroup,
        $connectonType,
        $subConnectionType,
        $connectionGroups = [],
        $connectonTypes = [],
        $subConnectionTypes = [],
        $meterTypes = [],
        $manufacturers = [],
        $cities = [],
        $meterTariffs = [];


    protected function createTestData(
        $cityCount = 1,
    ) {
        $this->user = UserFactory::new()->create();
        while ($cityCount > 0) {
            $city = CityFactory::new()->create();
            $this->cities[] = $city;
            $cityCount--;
        }
        $this->city = $this->cities[0];
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();


        $this->person = PersonFactory::new()->create();

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

    protected function createMeterWithTransaction()
    {
        $meter = $this->getMeter();
        $this->transaction = TransactionFactory::new()->create([
            'id' => 1,
            'amount' => $this->faker->unique()->randomNumber(),
            'sender' => $this->faker->phoneNumber,
            'message' => $meter->serial_number,
            'original_transaction_id' => $this->faker->unique()->randomNumber(),
            'original_transaction_type' => 'airtel_transaction',
        ]);
        $this->token = MeterTokenFactory::new()->create([
            'meter_id' => $meter->id,
            'token' => $this->faker->unique()->randomNumber(),
        ]);
        $paymentHistory = PaymentHistoryFactory::new()->create([
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'payment_service' => 'airtel_transaction',
            'sender' => $this->faker->phoneNumber,
            'payment_type' => 'energy',
            'paid_for_type' => 'token',
            'paid_for_id' => $this->token->id,
            'payer_type' => 'person',
            'payer_id' => $this->person->id,
        ]);
        return $meter;
    }

    protected function createMetersWithDifferentMeterTypes($meterCountPerMeterType = 1): void
    {

        $meterTypeCount = count($this->meterTypes);

        while ($meterTypeCount > 0) {

            while ($meterCountPerMeterType > 0) {
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
                $meterCountPerMeterType--;
            }
            $meterTypeCount--;
        }

    }

    protected function createMeterManufacturer($manufacturerCount = 1): void
    {
        while ($manufacturerCount > 0) {

            $manufacturer = ManufacturerFactory::new()->create();
            $address = Address::query()->make([
                'email' => $this->faker->email,
                'phone' => $this->faker->phoneNumber,
                'street' => $this->faker->streetAddress,
                'city_id' => 1,
            ]);
            $address->owner()->associate($manufacturer);
            $address->save();

            $this->manufacturers[] = $manufacturer;


            $manufacturerCount--;
        }
        if(count($this->manufacturers) > 0){
            $this->manufacturer = $this->manufacturers[0];
        }

    }

    protected function createMeterTariff($meterTariffCount = 1, $withTimeOfUsage = false): void
    {
        while ($meterTariffCount > 0) {
            $meterTariff = MeterTariffFactory::new()->create();
            $this->meterTariffs[] = $meterTariff;

            if($withTimeOfUsage){
                $timeOfUsage = TimeOfUsageFactory::new()->create([
                    'tariff_id' => $meterTariff->id,
                    'start'=>'00:00',
                    'end'=>'01:00',
                    'value'=>$this->faker->randomFloat(2, 0, 10),
                ]);
            }

            $meterTariffCount--;
        }
        if (count($this->meterTariffs) > 0) {
            $this->meterTariff = $this->meterTariffs[0];
        }

    }

    protected function createConnectionType($connectionTypeCount = 1, $subConnectionTypeCount = 1): void
    {
        while ($connectionTypeCount > 0) {
            $connectionType = ConnectionTypeFactory::new()->create();
            $this->connectonTypes[] = $connectionType;

            while ($subConnectionTypeCount > 0) {
                $subConnectionType =
                    SubConnectionTypeFactory::new()->create([
                        'connection_type_id' => $connectionType->id,
                        'tariff_id' => $this->meterTariff->id
                    ]);
                $this->subConnectionTypes[] = $subConnectionType;

                $subConnectionTypeCount--;
            }
            if(count($this->subConnectionTypes) > 0){
                $this->subConnectionType = $this->subConnectionTypes[0];
            }
            $connectionTypeCount--;
        }
        if(count($this->connectonTypes) > 0){
            $this->connectionType = $this->connectonTypes[0];
        }
    }

    protected function createConnectionGroup($connectionGroupCount = 1): void
    {
        while ($connectionGroupCount > 0) {
            $connectionGroup = ConnectionGroupFactory::new()->create();
            $this->connectionGroups[] = $connectionGroup;
            $connectionGroupCount--;
        }
        if(count($this->connectionGroups) > 0){
            $this->connectionGroup = $this->connectionGroups[0];
        }

    }

    protected function createMeterType($meterTypeCount = 1): void
    {
        while ($meterTypeCount > 0) {
            $meterType = MeterTypeFactory::new()->create();
            $this->meterTypes[] = $meterType;

            $meterTypeCount--;
        }

        if(count($this->meterTypes) > 0){
            $this->meterType = $this->meterTypes[0];
        }
    }

    protected function createMeter($meterCount =1): void
    {

        while ($meterCount > 0) {
            $meter = MeterFactory::new()->create([
                'meter_type_id' => $this->meterType->id,
                'in_use' => true,
                'manufacturer_id' => $this->getRandomIdFromList($this->manufacturers),
                'serial_number' => str_random(36),
            ]);
            $geographicalInformation = GeographicalInformation::query()->make(['points' => '111,222']);
            $person = PersonFactory::new()->create();
            $addressData = [
                'city_id' => $this->getRandomIdFromList($this->cities),
                'geo_id' => $geographicalInformation->id,
            ];
            $meterParameter = MeterParameterFactory::new()->create([
                'owner_type' => 'person',
                'owner_id' => $person->id,
                'meter_id' => $meter->id,
                'tariff_id' => $this->getRandomIdFromList($this->meterTariffs),
                'connection_type_id' => $this->getRandomIdFromList($this->connectonTypes),
                'connection_group_id' => $this->getRandomIdFromList($this->connectionGroups),
            ]);
            $address = Address::query()->make([
                'email' => $addressData['email'] ?? null,
                'phone' => $addressData['phone'] ?? null,
                'street' => $addressData['street'] ?? null,
                'city_id' => $addressData['city_id'] ?? null,
                'geo_id' => $addressData['geo_id'] ?? null,
                'is_primary' => $addressData['is_primary'] ?? 0,
            ]);
            $address->owner()->associate($meterParameter)->save();
            $geographicalInformation->owner()->associate($meterParameter)->save();

            $meterCount--;
        }

    }

    private function getRandomIdFromList(array $list, $startIndex=1, $endIndex = null): int
    {

        $ids = collect($list)->pluck('id')->toArray();

        if ($endIndex === null) {
            $endIndex = count($ids);
        }

        return rand($startIndex, $endIndex);
    }
}