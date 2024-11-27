<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class TargetTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsTargetList() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createConnectionGroup();
        $targetCount = 2;
        $this->createTarget($targetCount);
        $response = $this->actingAs($this->user)->get('/api/targets');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $targetCount);
    }

    public function testUserGetsTargetById() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createConnectionGroup();
        $targetCount = 2;
        $this->createTarget($targetCount);
        $response = $this->actingAs($this->user)->get(sprintf('/api/targets/%s', $this->targets[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->targets[0]->id);
    }

    public function testUserCreatesNewTarget() {
        $this->withExceptionHandling();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createConnectionGroup();
        $targetData = [
            'data' => [
                [
                    'id' => 1,
                    'name' => 'default connection group',
                    'target' => [
                        'newConnection' => 0,
                        'totalRevenue' => 0,
                        'connectedPower' => 0,
                        'energyPerMonth' => 0,
                        'averageRevenuePerMonth' => 0,
                    ],
                ],
            ],
            'period' => '2022-05-12',
            'targetType' => 'cluster',
            'targetId' => 1,
        ];
        $response = $this->actingAs($this->user)->post('/api/targets', $targetData);
        $response->assertStatus(201);
        $this->assertEquals($response['data']['type'], $targetData['targetType']);
        $this->assertEquals($response['data']['target_date'], $targetData['period']);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
