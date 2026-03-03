<?php

namespace Tests\Feature;

use App\Models\ConnectionType;
use Database\Factories\ConnectionTypeFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class ConnectionTypeTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private array $connectonTypeIds = [];

    public function testUserGetsConnectionTypeList(): void {
        $connectionTypeCount = 5;
        $this->createTestData($connectionTypeCount);
        $expectedCount = ConnectionType::query()->count();
        $response = $this->actingAs($this->user)->get('/api/connection-types');
        $response->assertStatus(200);
        $this->assertCount($expectedCount, $response['data']);
    }

    public function testUserGetsConnectionTypeById(): void {
        $this->createTestData(1);
        $response = $this->actingAs($this->user)->get(sprintf('/api/connection-types/%s', $this->connectonTypeIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->connectonTypeIds[0]);
    }

    public function testUserCreatesNewConnectionType(): void {
        $this->createTestData(0);
        $connectionTypeData = ['name' => 'Test Connection Type'];
        $response = $this->actingAs($this->user)->post('/api/connection-types', $connectionTypeData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $connectionTypeData['name']);
    }

    public function testUserUpdatesAConnectionType(): void {
        $this->createTestData(1);
        $connectionTypeData = ['name' => 'Updated Connection Type'];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/connection-types/%s',
            $this->connectonTypeIds[0]
        ), $connectionTypeData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $connectionTypeData['name']);
    }

    protected function createTestData($connectionTypeCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->assignRole($this->user, 'admin');

        while ($connectionTypeCount > 0) {
            $connectionType = ConnectionTypeFactory::new()->create();
            $this->connectonTypeIds[] = $connectionType->id;
            --$connectionTypeCount;
        }
    }
}
