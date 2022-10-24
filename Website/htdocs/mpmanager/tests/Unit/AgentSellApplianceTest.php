<?php

namespace Tests\Unit;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentCommission;
use App\Models\AgentSoldAppliance;
use App\Models\AssetType;
use App\Models\Cluster;
use App\Models\MiniGrid;
use App\Models\PaymentHistory;
use App\Models\User;
use Database\Factories\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentSellApplianceTest extends TestCase
{
    use RefreshMultipleDatabases, WithFaker;
    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_agent_sell_appliance()
    {
        $this->initData();
        $data = [
            'agent_assigned_appliance_id' => 2,
            'person_id' => 1,
            'first_payment_date' => '2020-12-29T20:53:38Z',
            'down_payment' => 100,
            'tenure' => 5
        ];

        /** @var Agent $agent */
        $agent = Agent::query()->latest()->first();

        $this->actingAs($agent)->post('/api/app/agents/appliances', $data);

        AgentSoldAppliance::query()->create([
           'person_id' => 1,
           'agent_assigned_appliance_id' => 1
        ]);

        $paymentHistory = PaymentHistory::query()->latest()->first();

        $this->assertEquals($data['down_payment'], $paymentHistory->amount);
    }

    public function initData()
    {
        /** @var User $user */
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        PersonFactory::new()->create();
        Cluster::query()->create([
            'name' => 'Test Cluster',
            'manager_id' => 1,
            'geo_data' => '{"leaflet_id":903,"type":"manual","geojson":{"type":"Polygon",
            "coordinates":[[[-3.204747603780925,37.937924389032375],
            [-3.4220930701917984,37.93779565098191],
            [-3.2492230959644415,38.24208948955007]]]},
            "searched":false,"display_name":"test","selected":true,"draw_type":"draw","lat":-3.2920212566457216,"lon":38.039269843188116}'
        ]);
        MiniGrid::query()->create([
            'cluster_id' => 1,
            'name' => 'Test-Grid',
            'data_stream' => 0
        ]);
        Agent::query()->create([
            'person_id' => 1,
            'mini_grid_id' => 1,
            'agent_commission_id' => 1,
            'device_id' => 1,
            'name' => 'alper',
            'email' => 'a@b.com',
            'fire_base_token' => 'sadadadasd3',
            'password' => '123123',
            'connection' => 'why do we need this? '

        ]);

        AgentCommission::query()->create([
            'name' => 'alper',
            'energy_commission' => 21,
            'appliance_commission' => 3,
            'risk_balance' => -3
        ]);

        AssetType::query()->create([
            'name' => 'test',
            'price' => 100,
        ]);

        AgentAssignedAppliances::query()->create([
            'agent_id' => 1,
            'user_id' => 1,
            'appliance_type_id' => 1,
            'cost' => 100
        ]);
    }
}
