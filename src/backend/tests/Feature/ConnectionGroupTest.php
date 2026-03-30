<?php

namespace Tests\Feature;

use App\Models\ConnectionGroup;
use Database\Factories\ConnectionGroupFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class ConnectionGroupTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    private $user;
    private array $connectonGroupIds = [];

    public function testUserGetsConnectionGroupList(): void {
        $connectionGroupCount = 5;
        $this->createTestData($connectionGroupCount);
        $expectedCount = ConnectionGroup::query()->count();
        $response = $this->actingAs($this->user)->get('/api/connection-groups');
        $response->assertStatus(200);
        $this->assertCount($expectedCount, $response['data']);
    }

    public function testUserGetsConnectionGroupById(): void {
        $this->createTestData(1);
        $response = $this->actingAs($this->user)->get(sprintf('/api/connection-groups/%s', $this->connectonGroupIds[0]));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->connectonGroupIds[0]);
    }

    public function testUserCreatesNewConnectionGroup(): void {
        $this->createTestData(0);
        $connectionGroupData = ['name' => 'Test Connection Group'];
        $response = $this->actingAs($this->user)->post('/api/connection-groups', $connectionGroupData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['name'], $connectionGroupData['name']);
    }

    public function testUserUpdatesAConnectionGroup(): void {
        $this->createTestData(1);
        $connectionGroupData = ['name' => 'Updated Connection Group'];
        $response = $this->actingAs($this->user)->put(sprintf(
            '/api/connection-groups/%s',
            $this->connectonGroupIds[0]
        ), $connectionGroupData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['name'], $connectionGroupData['name']);
    }

    protected function createTestData($connectionGroupCount = 1) {
        $this->user = UserFactory::new()->create();
        $this->assignRole($this->user, 'admin');

        while ($connectionGroupCount > 0) {
            $connectionGroup = ConnectionGroupFactory::new()->create();
            $this->connectonGroupIds[] = $connectionGroup->id;
            --$connectionGroupCount;
        }
    }
}
