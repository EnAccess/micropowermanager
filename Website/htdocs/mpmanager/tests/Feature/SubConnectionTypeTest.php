<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubConnectionTypeTest extends TestCase
{
    use CreateEnvironments;

    public function test_user_gets_sub_connection_type_list()
    {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $this->createTestData($connectionTypeCount, $subConnectionTypeCount);
        $response = $this->actingAs($this->user)->get('/api/sub-connection-types');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->subConnectonTypeIds));
    }

    public function test_user_gets_sub_connection_types_by_connection_type_id()
    {
        $connectionTypeCount = 2;
        $subConnectionTypeCount = 1;
        $this->createTestData($connectionTypeCount, $subConnectionTypeCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/sub-connection-types/%s',
            $this->connectonTypeIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data'][0]['connection_type_id'], $this->connectonTypeIds[0]);
    }

    public function test_user_creates_new_sub_connection_type()
    {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 0;
        $this->createTestData($connectionTypeCount, $subConnectionTypeCount);
        $subConnectionTypeData = [
            'name' => 'Test SubConnection Type',
            'connection_type_id' => $this->connectonTypeIds[0],
            'tariff_id' => $this->meterTariff->id
        ];
        $response = $this->actingAs($this->user)->post('/api/sub-connection-types', $subConnectionTypeData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $subConnectionTypeData['name']);
    }

    public function test_user_updates_a_sub_connection_type()
    {
        $connectionTypeCount = 1;
        $subConnectionTypeCount = 1;
        $this->createTestData($connectionTypeCount, $subConnectionTypeCount);
        $subConnectionTypeData = ['name' => 'Updated SubConnection Type'];
        $response = $this->actingAs($this->user)->put(sprintf('/api/sub-connection-types/%s',
            $this->subConnectonTypeIds[0]), $subConnectionTypeData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $subConnectionTypeData['name']);
    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
