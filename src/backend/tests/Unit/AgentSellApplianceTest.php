<?php

namespace Tests\Unit;

use App\Jobs\ProcessPayment;
use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentCommission;
use App\Models\Appliance;
use App\Models\ApplianceRate;
use App\Models\City;
use App\Models\Cluster;
use App\Models\Device;
use App\Models\MiniGrid;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class AgentSellApplianceTest extends TestCase {
    use RefreshMultipleDatabases;
    use WithFaker;

    public function testAgentSaleRecordsTheDownPaymentAsAnOutstandingRateAndPaysItThroughTheQueue(): void {
        Queue::fake();
        $data = $this->initData();

        $agent = Agent::query()->latest()->first();

        $response = $this->actingAs($agent)->post('/api/app/agents/appliances/', $data);

        $response->assertStatus(201);

        $downPaymentRate = ApplianceRate::query()
            ->where('rate_cost', $data['down_payment'])
            ->whereDate('due_date', Carbon::today())
            ->firstOrFail();
        $this->assertSame($data['down_payment'], $downPaymentRate->remaining);

        $transaction = Transaction::query()->where('type', Transaction::TYPE_DOWN_PAYMENT)->firstOrFail();
        $this->assertSame($data['device_serial'], $transaction->message);
        $this->assertSame($transaction->id, $response->json('data.transaction_id'));

        Queue::assertPushed(ProcessPayment::class);
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
            'risk_balance' => 10000,
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

        $city = City::query()->create([
            'name' => 'Test City',
            'country_id' => 1,
            'cluster_id' => $cluster->id,
            'mini_grid_id' => $miniGrid->id,
        ]);

        $shs = SolarHomeSystem::query()->create([
            'serial_number' => 'SHS-TEST-0001',
            'manufacturer_id' => 1,
            'appliance_id' => $appliance->id,
        ]);

        Device::query()->create([
            'person_id' => $person->id,
            'device_id' => $shs->id,
            'device_type' => SolarHomeSystem::class,
            'device_serial' => 'SHS-TEST-0001',
        ]);

        return [
            'agent_assigned_appliance_id' => $agentAssignedAppliance->id,
            'person_id' => $person->id,
            'first_payment_date' => Carbon::today()->toIso8601String(),
            'down_payment' => 100,
            'tenure' => 5,
            'device_serial' => 'SHS-TEST-0001',
            'address' => [
                'street' => '1 Test Street',
                'city_id' => $city->id,
            ],
            'points' => '0,0',
        ];
    }
}
