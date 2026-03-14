<?php

namespace Tests\Feature;

use App\Models\Agent;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentList(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $agentCount = 4;
        $this->createAgent($agentCount);
        $response = $this->actingAs($this->user)->get('/api/agents');
        $response->assertStatus(200);
        $this->assertEquals($agentCount, count($response['data']));
    }

    public function testUserGetsAgentById(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $this->createAgent(4);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->agents[0]->id);
    }

    public function testUserCreatesNewAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $postData = [
            'name' => $this->faker->name(),
            'surname' => $this->faker->name(),
            'birth_date' => $this->faker->date(),
            'password' => $this->faker->password(),
            'email' => $this->faker->unique()->safeEmail(),
            'mini_grid_id' => $this->miniGrid->id,
            'phone' => $this->faker->phoneNumber(),
            'agent_commission_id' => $this->agentCommissions[0]->id,
            'village_id' => $this->village->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents', $postData);
        $response->assertStatus(201);
        $this->assertNotNull($response['data']['person_id']);
        $lastCreatedAgent = Agent::query()->find($response['data']['id']);
        $personAddress = $lastCreatedAgent->person->addresses()->first();
        $this->assertEquals($personAddress->phone, $postData['phone']);
    }

    public function testUserCanUpdateAnAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $this->createAgent();

        $putData = [
            'personId' => $this->agents[0]->person->id,
            'name' => 'updated name',
            'surname' => 'updated surname',
            'birthday' => $this->faker->date(),
            'phone' => $this->faker->phoneNumber(),
            'gender' => 'male',
            'commissionTypeId' => $this->agentCommissions[0]->id,
        ];

        $response = $this->actingAs($this->user)->put(sprintf('/api/agents/%s', $this->agents[0]->id), $putData);
        $response->assertStatus(200);
        $this->assertEquals($putData['name'], $response['data']['person']['name']);
        $this->assertEquals($putData['phone'], $response['data']['person']['addresses'][0]['phone']);
        $this->assertEquals($putData['gender'], $response['data']['person']['gender']);
    }

    public function testUserCanResetsAgentsPassword(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $this->createAgent();

        $putData = [
            'email' => $this->agents[0]->email,
        ];

        $response = $this->actingAs($this->user)->post('/api/agents/reset-password', $putData);
        $response->assertStatus(200);
    }

    public function testUserCanSearchAnAgentByName(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $this->createAgent();

        $response = $this->actingAs($this->user)->get('/api/agents/search?term='.$this->agents[0]->person->name);
        $response->assertStatus(200);
        $responseData = $response['data'][0];
        $this->assertEquals($responseData['person']['name'], $this->agents[0]->person->name);
    }

    public function testUserCanDeleteAnAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createVillage();
        $this->createAgentCommission();
        $this->createAgent();
        $this->actingAs($this->user)->delete(sprintf('/api/agents/%s', $this->agents[0]->id));
        $agentsCount = Agent::query()->get()->count();
        $this->assertEquals(0, $agentsCount);
    }
}
