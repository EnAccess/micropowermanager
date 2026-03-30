<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentCommission;
use App\Models\AgentSoldAppliance;
use App\Models\Appliance;
use App\Models\Cluster;
use App\Models\MiniGrid;
use App\Models\PaymentHistory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class AgentSellApplianceTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    /**
     * A basic unit test example.
     */
    public function testAgentSellAppliance(): void {
        $data = $this->initData();

        $agent = Agent::query()->latest()->first();

        $response = $this->actingAs($agent)->post('/api/app/agents/appliances/', $data);

        $response->assertStatus(201);

        AgentSoldAppliance::query()->create([
            'person_id' => 1,
            'agent_assigned_appliance_id' => 1,
        ]);

        $paymentHistory = PaymentHistory::query()->latest()->first();

        $this->assertEquals($data['down_payment'], $paymentHistory->amount);
    }

    public function initData(): array {
        $user = UserFactory::new()->create(['company_id' => $this->companyId]);
        $this->actingAs($user);
        $person = PersonFactory::new()->create();
        $cluster = Cluster::query()->create([
            'name' => 'Test Cluster',
            'manager_id' => 1,
            'geo_json' => json_encode([
                'type' => 'Feature',
                'properties' => [
                    'name' => 'Test Cluster',
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [
                        [
                            [37.937924389032375, -3.204747603780925],
                            [37.93779565098191, -3.4220930701917984],
                            [38.24208948955007, -3.2492230959644415],
                            [37.937924389032375, -3.204747603780925],
                        ],
                    ],
                ],
            ]),
        ]);

        $miniGrid = MiniGrid::query()->create([
            'cluster_id' => $cluster->id,
            'name' => 'Test-Grid',
        ]);

        $agent_commission = AgentCommission::query()->create([
            'name' => 'alper',
            'energy_commission' => 21,
            'appliance_commission' => 3,
            'risk_balance' => -3,
        ]);

        $agent = Agent::query()->create([
            'person_id' => $person->id,
            'mini_grid_id' => $miniGrid->id,
            'agent_commission_id' => $agent_commission->id,
            'mobile_device_id' => 1,
            'email' => 'a@a.com',
            'fire_base_token' => 'sadadadasd3',
            'password' => '123123',
            'connection' => 'tenant',
            'balance' => 200,
        ]);

        $appliance = Appliance::query()->create([
            'name' => 'test',
            'price' => 100,
            'appliance_type_id' => 1,
        ]);

        $agentAssignedAppliance = AgentAssignedAppliances::query()->create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'appliance_id' => $appliance->id,
            'cost' => 100,
        ]);

        return [
            'agent_assigned_appliance_id' => $agentAssignedAppliance->id,
            'person_id' => $person->id,
            'first_payment_date' => '2020-12-29T20:53:38Z',
            'down_payment' => 100,
            'tenure' => 5,
        ];
    }
}
