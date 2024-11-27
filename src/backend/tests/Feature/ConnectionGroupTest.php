<?php

namespace Tests\Feature;

use Database\Factories\CompanyDatabaseFactory;
use Database\Factories\CompanyFactory;
use Database\Factories\ConnectionGroupFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConnectionGroupTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private $company;
    private $companyDatabase;
    private $person;
    private $connectonGroupIds = [];

    public function testUserGetsConnectionGroupList() {
        $connectionGroupCount = 5;
        $this->createTestData($connectionGroupCount);
        $response = $this->actingAs($this->user)->get('/api/connection-groups');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), count($this->connectonGroupIds));
    }

    public function testUserGetsConnectionGroupById() {
        $connectionGroupCount = 5;
        $this->createTestData($connectionGroupCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/connection-groups/%s', $this->connectonGroupIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->connectonGroupIds[0]);
    }

    public function testUserCreatesNewConnectionGroup() {
        $connectionGroupCount = 0;
        $this->createTestData($connectionGroupCount);
        $connectionGroupData = ['name' => 'Test Connection Group'];
        $response = $this->actingAs($this->user)->post('/api/connection-groups', $connectionGroupData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $connectionGroupData['name']);
    }

    public function testUserUpdatesAConnectionGroup() {
        $connectionGroupCount = 1;
        $this->createTestData($connectionGroupCount);
        $connectionGroupData = ['name' => 'Updated Connection Group'];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/connection-groups/%s',
            $this->connectonGroupIds[0]
        ), $connectionGroupData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $connectionGroupData['name']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    protected function createTestData($connectionGroupCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->company = CompanyFactory::new()->create();
        $this->companyDatabase = CompanyDatabaseFactory::new()->create();

        while ($connectionGroupCount > 0) {
            $connectionGroup = ConnectionGroupFactory::new()->create();
            array_push($this->connectonGroupIds, $connectionGroup->id);
            --$connectionGroupCount;
        }
    }
}
