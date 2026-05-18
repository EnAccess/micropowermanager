<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Support\Facades\Hash;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentList(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
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
        $this->createCity();
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
        $this->createCity();
        $this->createAgentCommission();
        $postData = [
            'name' => $this->faker->name(),
            'surname' => $this->faker->name(),
            'birth_date' => $this->faker->date(),
            'password' => $this->faker->password(),
            'email' => $this->faker->unique()->safeEmail(),
            'mini_grid_id' => $this->miniGrid->id,
            'phone' => $this->faker->e164PhoneNumber(),
            'agent_commission_id' => $this->agentCommissions[0]->id,
            'city_id' => $this->city->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents', $postData);
        $response->assertStatus(201);
        $this->assertNotNull($response['data']['person_id']);
        $lastCreatedAgent = Agent::query()->find($response['data']['id']);
        $personAddress = $lastCreatedAgent->person->addresses()->first();
        $this->assertEquals($personAddress->phone, phone($postData['phone'])->formatE164());
    }

    public function testUserCanUpdateAnAgent(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $putData = [
            'personId' => $this->agents[0]->person->id,
            'name' => 'updated name',
            'surname' => 'updated surname',
            'birthday' => $this->faker->date(),
            'phone' => $this->faker->e164PhoneNumber(),
            'gender' => 'male',
            'commissionTypeId' => $this->agentCommissions[0]->id,
        ];

        $response = $this->actingAs($this->user)->put(sprintf('/api/agents/%s', $this->agents[0]->id), $putData);
        $response->assertStatus(200);
        $this->assertEquals($putData['name'], $response['data']['person']['name']);
        $this->assertEquals(phone($putData['phone'])->formatE164(), $response['data']['person']['addresses'][0]['phone']);
        $this->assertEquals($putData['gender'], $response['data']['person']['gender']);
    }

    public function testAdminCanChangeAgentPassword(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $newPassword = 'new-secret-123';
        $response = $this->actingAs($this->user)->put(
            sprintf('/api/agents/%s/password', $this->agents[0]->id),
            [
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ],
        );

        $response->assertStatus(200);
        $this->assertTrue(Hash::check($newPassword, $this->agents[0]->fresh()->password));
    }

    public function testChangeAgentPasswordRejectsMismatchedConfirmation(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $response = $this->actingAs($this->user)->putJson(
            sprintf('/api/agents/%s/password', $this->agents[0]->id),
            [
                'password' => 'new-secret-123',
                'password_confirmation' => 'does-not-match',
            ],
        );

        $response->assertStatus(422);
    }

    public function testUserCanUpdateAgentMiniGrid(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid(2);
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $targetMiniGrid = $this->miniGrids[1];

        $response = $this->actingAs($this->user)->put(
            sprintf('/api/agents/%s', $this->agents[0]->id),
            ['miniGridId' => $targetMiniGrid->id],
        );

        $response->assertStatus(200);
        $this->assertEquals($targetMiniGrid->id, $this->agents[0]->fresh()->mini_grid_id);
    }

    public function testAgentCanResetOwnPasswordFromApp(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $response = $this->post('/api/app/reset-password', [
            'email' => $this->agents[0]->email,
        ]);
        $response->assertStatus(200);
    }

    public function testUserCanSearchAnAgentByName(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
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
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->actingAs($this->user)->delete(sprintf('/api/agents/%s', $this->agents[0]->id));
        $agentsCount = Agent::query()->get()->count();
        $this->assertEquals(0, $agentsCount);
    }
}
