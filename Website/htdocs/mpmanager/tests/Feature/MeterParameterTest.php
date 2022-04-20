<?php

namespace Tests\Feature;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionGroupFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MeterFactory;
use Database\Factories\MeterTariffFactory;
use Database\Factories\MeterTypeFactory;
use Database\Factories\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeterParameterTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;

    public function test_user_assigns_a_meter_parameter_to_a_meter()
    {
        $this->withExceptionHandling();
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $meterType = MeterTypeFactory::new()->create();
        $meterTariff = MeterTariffFactory::new()->create();
        $manufacturer = ManufacturerFactory::new()->create();
        $meter = MeterFactory::new()->create();
        $person = PersonFactory::new()->create();
        $connectionType = ConnectionTypeFactory::new()->create();
        $connectionGroup = ConnectionGroupFactory::new()->create();
        $meterParameterData = [
            'meter_id' => $meter->id,
            'tariff_id' => $meterTariff->id,
            'customer_id' => $person->id,
            'connection_type_id' => $connectionType->id,
            'connection_group_id' => $connectionGroup->id,
            'geo_points' => '123123,123123',
            'city_id' => $city->id,
        ];
        $response = $this->actingAs($user)->post('/api/meters/parameters', $meterParameterData);
        $response->assertStatus(201);
        $meterAddress = Address::query()
            ->where('owner_type', 'meter_parameter')
            ->where('owner_id', $response['data']['id'])
            ->first();
        $geographicalInformation = GeographicalInformation::query()
            ->where('owner_type', 'meter_parameter')
            ->where('owner_id', $response['data']['id'])
            ->first();
        $this->assertNotNull($meterAddress);
        $this->assertEquals($geographicalInformation->points, $meterParameterData['geo_points']);

    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
