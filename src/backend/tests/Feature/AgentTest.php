<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Person\Person;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentList() {
        $this->createTestData();
        $agentCount = 4;
        $this->createAgent(4);
        $response = $this->actingAs($this->user)->get('/api/agents');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentCount);
    }

    public function testUserGetsAgentById() {
        $this->createTestData();
        $agentCount = 4;
        $this->createAgent(4);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals($response['data']['id'], $this->agents[0]->id);
    }

    public function testUserCreatesNewAgent() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $postData = [
            'name' => $this->faker->name,
            'surname' => $this->faker->name,
            'birth_date' => $this->faker->date(),
            'password' => $this->faker->password,
            'email' => $this->faker->unique()->safeEmail,
            'mini_grid_id' => $this->miniGrid->id,
            'phone' => $this->faker->phoneNumber,
            'agent_commission_id' => $this->agentCommissions[0]->id,
            'city_id' => $this->city->id,
        ];
        $response = $this->actingAs($this->user)->post('/api/agents', $postData);
        $response->assertStatus(201);
        $lastCreatedPerson = Person::query()->latest()->first();
        $lastCreatedAgent = Agent::query()->latest()->first();
        $personAddress = $lastCreatedPerson->addresses()->first();
        $this->assertEquals($lastCreatedPerson->id, $response['data']['person_id']);
        $this->assertEquals($lastCreatedAgent->person->name, $response['data']['name']);
        $this->assertEquals($personAddress->phone, $postData['phone']);
    }

    public function testUserCanUpdateAnAgent() {
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
            'phone' => $this->faker->phoneNumber,
            'gender' => 'male',
            'commissionTypeId' => 1,
        ];

        $response = $this->actingAs($this->user)->put(sprintf('/api/agents/%s', $this->agents[0]->id), $putData);
        $response->assertStatus(200);
        $this->assertEquals($putData['name'], $response['data']['name']);
        $this->assertEquals($putData['phone'], $response['data']['person']['addresses'][0]['phone']);
        $this->assertEquals($putData['gender'], $response['data']['person']['sex']);
    }

    public function testUserCanResetsAgentsPassword() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $putData = [
            'email' => $this->agents[0]->email,
        ];

        $response = $this->actingAs($this->user)->post('/api/agents/reset-password', $putData);
        $response->assertStatus(200);
    }

    public function testUserCanSearchAnAgentByName() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $response = $this->actingAs($this->user)->get('/api/agents/search?q='.$this->agents[0]->person->name);
        $responseData = $response['data'][0];
        $this->assertEquals($responseData['name'], $this->agents[0]->person->name);
    }

    public function testUserCanDeleteAnAgent() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $response = $this->actingAs($this->user)->delete(sprintf('/api/agents/%s', $this->agents[0]->id));
        $agentsCount = Agent::query()->get()->count();
        $this->assertEquals(0, $agentsCount);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
