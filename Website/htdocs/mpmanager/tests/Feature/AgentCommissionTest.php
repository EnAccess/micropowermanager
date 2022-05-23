<?php

namespace Tests\Feature;

use App\Models\AgentCommission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentCommissionTest extends TestCase
{
    use CreateEnvironments;


    public function test_user_gets_agent_commission_list()
    {
        $this->createTestData();
        $agentCommissionCount = 5;
        $this->createAgentCommission($agentCommissionCount);
        $response = $this->actingAs($this->user)->get('/api/agents/commissions');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentCommissionCount);
    }

    public function test_user_creates_agent_commission()
    {

        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $postData = [
            'name'=>'test commission',
            'energy_commission'=>0.5,
            'appliance_commission'=>0.5,
            'risk_balance' => -10000
        ];
        $response = $this->actingAs($this->user)->post('/api/agents/commissions', $postData);
        $response->assertStatus(201);
        $lastCreatedAgentCommission = AgentCommission::query()->latest()->first();
        $this->assertEquals($lastCreatedAgentCommission->name, $response['data']['name']);

    }

    public function test_user_can_update_an_agent_commission()
    {

        $this->createTestData();
        $this->createAgentCommission();
        $putData = [
            'name'=>'updated commission',
            'energy_commission'=>1.5,
            'appliance_commission'=>1.5,
            'risk_balance' => -20000
        ];

        $response = $this->actingAs($this->user)->put(sprintf('/api/agents/commissions/%s',
            $this->agentCommissions[0]->id), $putData);
        $response->assertStatus(200);
        $this->assertEquals($putData['name'], $response['data']['name']);
        $this->assertEquals($putData['energy_commission'], $response['data']['energy_commission']);
        $this->assertEquals($putData['appliance_commission'], $response['data']['appliance_commission']);
        $this->assertEquals($putData['risk_balance'], $response['data']['risk_balance']);

    }

    public function test_user_can_delete_an_agent()
    {
        $this->createTestData();
        $this->createAgentCommission();
        $response = $this->actingAs($this->user)->delete(sprintf('/api/agents/commissions/%s', $this->agentCommissions[0]->id));
        $agentsCount = AgentCommission::query()->get()->count();
        $this->assertEquals(0, $agentsCount);
    }


    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
