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
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentSellApplianceTest extends TestCase {
    use RefreshDatabase;
    use WithFaker;

    public function actingAs(Authenticatable $user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }

    /**
     * A basic unit test example.
     */
    public function testAgentSellAppliance(): void {
        $this->initData();
        $data = [
            'agent_assigned_appliance_id' => 2,
            'person_id' => 1,
            'first_payment_date' => '2020-12-29T20:53:38Z',
            'down_payment' => 100,
            'tenure' => 5,
        ];

        $agent = Agent::query()->latest()->first();

        $this->actingAs($agent)->post('/api/app/agents/appliances', $data);

        AgentSoldAppliance::query()->create([
            'person_id' => 1,
            'agent_assigned_appliance_id' => 1,
        ]);

        $paymentHistory = PaymentHistory::query()->latest()->first();

        $this->assertEquals($data['down_payment'], $paymentHistory->amount);
    }

    public function initData(): void {
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        PersonFactory::new()->create();
        Cluster::query()->create([
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

        MiniGrid::query()->create([
            'cluster_id' => 1,
            'name' => 'Test-Grid',
        ]);
        Agent::query()->create([
            'person_id' => 1,
            'mini_grid_id' => 1,
            'agent_commission_id' => 1,
            'mobile_device_id' => 1,
            'name' => 'alper',
            'email' => 'a@a.com',
            'fire_base_token' => 'sadadadasd3',
            'password' => '123123',
        ]);

        AgentCommission::query()->create([
            'name' => 'alper',
            'energy_commission' => 21,
            'appliance_commission' => 3,
            'risk_balance' => -3,
        ]);

        Appliance::query()->create([
            'name' => 'test',
            'price' => 100,
            'appliance_type_id' => 1,
        ]);

        AgentAssignedAppliances::query()->create([
            'agent_id' => 1,
            'user_id' => 1,
            'appliance_id' => 1,
            'cost' => 100,
        ]);
    }
}
