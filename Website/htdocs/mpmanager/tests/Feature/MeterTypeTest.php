<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeterTypeTest extends TestCase
{
    use CreateEnvironments;

    public function test_user_gets_meter_type_list()
    {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $meterTypeCount = 5;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $this->createMeterType($meterTypeCount);
        $response = $this->actingAs($this->user)->get('/api/meter-types');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->meterTypes));
    }

    public function test_user_gets_meter_type_by_id()
    {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $meterTypeCount = 5;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $this->createMeterType($meterTypeCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/meter-types/%s', $this->meterTypes[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['max_current'], $this->meterTypes[0]->max_current);
        $this->assertEquals($response['data']['phase'], $this->meterTypes[0]->phase);
    }

    public function test_user_creates_new_meter_type()
    {
        $connectionTypeCount = 0;
        $subConnectionTypeCount = 0;
        $meterTypeCount = 0;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterTariff($meterTariffCount);
        $this->createConnectionType($connectionTypeCount, $subConnectionTypeCount);
        $this->createMeterType($meterTypeCount);
        $meterTypeData = [
            'online' => $this->faker->numberBetween(0, 1),
            'phase' => 1,
            'max_current' => 10,
        ];
        $response = $this->actingAs($this->user)->post('/api/meter-types', $meterTypeData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['max_current'], $meterTypeData['max_current']);
        $this->assertEquals($response['data']['phase'], $meterTypeData['phase']);
    }

    public function test_user_updates_a_meter_type()
    {

        $meterTypeCount = 1;
        $meterTariffCount = 1;
        $this->createTestData();
        $this->createMeterType($meterTypeCount);
        $meterTypeData = [
            'online' => 1,
            'phase' => 3,
            'max_current' => 15,
        ];
        $response = $this->actingAs($this->user)->put(sprintf('/api/meter-types/%s',
            $this->meterTypes[0]->id), $meterTypeData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['max_current'], $meterTypeData['max_current']);
        $this->assertEquals($response['data']['phase'], $meterTypeData['phase']);
    }

    public function test_user_gets_meter_types_with_meter_relation_by_meter_type_id()
    {
        $connectionTypeCount = 2;
        $this->createTestData();
        $this->createMeterTariff();
        $this->createConnectionGroup();
        $this->createConnectionType($connectionTypeCount );
        $this->createMeterType();
        $this->createMeterManufacturer();
        $this->createPerson();
        $this->createMetersWithDifferentMeterTypes();
        $response = $this->actingAs($this->user)->get(sprintf('/api/meter-types/%s/list',
            $this->meterTypes[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']['meters']), 1);
    }


    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
