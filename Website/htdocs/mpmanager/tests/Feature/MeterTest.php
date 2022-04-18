<?php

namespace Tests\Feature;

use App\Models\Meter\Meter;
use Database\Factories\CityFactory;
use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\MeterFactory;
use Database\Factories\MeterParameterFactory;
use Database\Factories\MeterTariffFactory;
use Database\Factories\MeterTypeFactory;
use Database\Factories\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeterTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;

    public function test_user_gets_meter_list()
    {
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $meterType = MeterTypeFactory::new()->create();
        $meterTariff = MeterTariffFactory::new()->create();
        $connectionType = ConnectionTypeFactory::new()->create();
        $connectionGroup = ConnectionTypeFactory::new()->create();

        $meterCunt = 5;
        while ($meterCunt > 0) {
            $person = PersonFactory::new()->create();
            $meter = MeterFactory::new()->create([
                'meter_type_id' => $meterType->id,
                'in_use' => true,
                'manufacturer_id' => 1,
                'serial_number' => str_random(36),
            ]);
            $meterParameter = MeterParameterFactory::new()->create([
                'owner_type' => 'person',
                'owner_id' => $person->id,
                'meter_id' => $meter->id,
                'tariff_id' => $meterTariff->id,
                'connection_type_id' => $connectionType->id,
                'connection_group_id' => $connectionGroup->id,
            ]);
            $meterCunt--;
        }
        $response = $this->actingAs($user)->get('/api/meters');
        $response->assertStatus(200);
        $this->assertEquals(5, count($response['data']));
    }

    public function test_user_creates_a_meter()
    {
        $user = UserFactory::new()->create();
        $city = CityFactory::new()->create();
        $company = CompanyFactory::new()->create();
        $companyDatabase = CompanyDatabaseFactory::new()->create();
        $meterType = MeterTypeFactory::new()->create();
        $manufacturer = ManufacturerFactory::new()->create();
        $meterData = [
            'serial_number'=>"123456789",
            'meter_type_id'=>$meterType->id,
            'in_use'=>false,
            'manufacturer_id'=>$manufacturer->id,
        ];
        $response = $this->actingAs($user)->post('/api/meters', $meterData);
        $response->assertStatus(201);
        $meter = Meter::query()->latest('id')->first();
        $this->assertEquals($meter->serial_number, $meterData['serial_number']);
    }
    
    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }


}
