<?php

namespace Tests\Feature;

use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConnectionTypeTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $companyDatabase;
    private $person;
    private $connectonTypeIds = [];

    public function testUserGetsConnectionTypeList() {
        $connectionTypeCount = 5;
        $this->createTestData($connectionTypeCount);
        $response = $this->actingAs($this->user)->get('/api/connection-types');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->connectonTypeIds));
    }

    public function testUserGetsConnectionTypeById() {
        $connectionTypeCount = 5;
        $this->createTestData($connectionTypeCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/connection-types/%s', $this->connectonTypeIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->connectonTypeIds[0]);
    }

    public function testUserCreatesNewConnectionType() {
        $connectionTypeCount = 0;
        $this->createTestData($connectionTypeCount);
        $connectionTypeData = ['name' => 'Test Connection Type'];
        $response = $this->actingAs($this->user)->post('/api/connection-types', $connectionTypeData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $connectionTypeData['name']);
    }

    public function testUserUpdatesAConnectionType() {
        $connectionTypeCount = 1;
        $this->createTestData($connectionTypeCount);
        $connectionTypeData = ['name' => 'Updated Connection Type'];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/connection-types/%s',
            $this->connectonTypeIds[0]
        ), $connectionTypeData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $connectionTypeData['name']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    protected function createTestData($connectionTypeCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();

        while ($connectionTypeCount > 0) {
            $connectionType = ConnectionTypeFactory::new()->create();
            array_push($this->connectonTypeIds, $connectionType->id);
            --$connectionTypeCount;
        }
    }
}
