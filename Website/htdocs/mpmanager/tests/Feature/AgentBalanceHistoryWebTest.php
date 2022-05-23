<?php

namespace Tests\Feature;

use App\Models\AgentBalanceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentBalanceHistoryWebTest extends TestCase
{
    use CreateEnvironments;

    public function test_user_gets_agents_balance_histories()
    {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agentTransactionCount = 1;
        $this->createAgentTransaction($agentTransactionCount);
        $agentBalanceHistoryCount = count(['agent_transaction','agent_commission']);
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/balance/history/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentBalanceHistoryCount);

    }

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
