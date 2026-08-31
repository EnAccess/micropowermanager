<?php

namespace Tests\Unit;

use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCommission;
use App\Models\AgentSoldAppliance;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\City;
use App\Models\Cluster;
use App\Models\Device;
use App\Models\MiniGrid;
use App\Models\MpmPlugin;
use App\Models\PaymentHistory;
use App\Models\Plugins;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\VodacomMzPaymentProvider\Http\Clients\VodacomMzApiClient;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
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

    public function testAgentSaleWithCashDownPaymentCreditsBalanceAndCommission(): void {
        $data = $this->initData();
        $agent = Agent::query()->latest()->first();
        $balanceBefore = (float) $agent->balance;

        $this->actingAs($agent)->post('/api/app/agents/appliances/', $data)->assertStatus(201);

        $transaction = Transaction::query()->where('type', Transaction::TYPE_DOWN_PAYMENT)->firstOrFail();
        $this->assertSame('agent_transaction', $transaction->original_transaction_type);
        $this->assertNull($transaction->agent_id);

        $agent = $agent->fresh();
        $this->assertSame($balanceBefore + 100, (float) $agent->balance);
        $this->assertSame(300.0, (float) $agent->commission_revenue);

        $rows = AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->get()->countBy('trigger_type');
        $this->assertSame(1, $rows[AgentAssignedAppliances::RELATION_NAME] ?? 0);
        $this->assertSame(1, $rows[AgentCommission::RELATION_NAME] ?? 0);
    }

    public function testAgentSaleWithProviderDownPaymentLeavesBalanceUntouched(): void {
        Queue::fake();
        $data = $this->initData();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();
        $agent = Agent::query()->latest()->first();
        $this->givePrimaryPhoneTo($data['person_id'], $data['address']['city_id']);
        $balanceBefore = (float) $agent->balance;

        $this->actingAs($agent)->post(
            '/api/app/agents/appliances/',
            $data + ['payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER],
        )->assertStatus(201);

        $transaction = Transaction::query()->where('type', Transaction::TYPE_DOWN_PAYMENT)->firstOrFail();
        $this->assertSame('vodacom_mz_transaction', $transaction->original_transaction_type);
        $this->assertSame($agent->id, $transaction->agent_id);

        // Nothing is settled until the payment is processed: no paid rate, no ledger movement.
        $this->assertSame(0, PaymentHistory::query()->count());
        $this->assertSame(0, AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame($balanceBefore, (float) $agent->fresh()->balance);
        Queue::assertPushed(ProcessPayment::class);

        event(new TransactionSuccessfulEvent($transaction));

        $agent = $agent->fresh();
        $this->assertSame($balanceBefore, (float) $agent->balance);
        $this->assertSame(300.0, (float) $agent->commission_revenue);

        $rows = AgentBalanceHistory::query()->where('transaction_id', $transaction->id)->get();
        $this->assertCount(1, $rows);
        $this->assertSame(AgentCommission::RELATION_NAME, $rows->first()->trigger_type);
    }

    public function testAgentSaleRollsBackWhenProviderPaymentIsRejected(): void {
        Queue::fake();
        $data = $this->initData();
        $this->enableVodacom();
        $agent = Agent::query()->latest()->first();
        $this->givePrimaryPhoneTo($data['person_id'], $data['address']['city_id']);

        $apiClient = $this->createMock(VodacomMzApiClient::class);
        $apiClient->method('c2bPayment')->willReturn([
            'output_ResponseCode' => 'INS-2006',
            'output_ResponseDesc' => 'Not enough balance',
        ]);
        $this->app->instance(VodacomMzApiClient::class, $apiClient);

        $response = $this->actingAs($agent)->postJson(
            '/api/app/agents/appliances/',
            $data + ['payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER],
        );

        $response->assertStatus(502);

        // An appliance whose down payment was declined must not be recorded as sold.
        $this->assertSame(0, AppliancePerson::query()->count());
        $this->assertSame(0, AgentSoldAppliance::query()->count());
        $this->assertSame(0, ApplianceRate::query()->count());
        $this->assertSame(0, Transaction::query()->count());
        Queue::assertNotPushed(ProcessPayment::class);
    }

    public function testProviderDownPaymentIsNotBlockedByRiskBalance(): void {
        Queue::fake();
        $data = $this->initData();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();
        $agent = Agent::query()->latest()->first();
        $this->givePrimaryPhoneTo($data['person_id'], $data['address']['city_id']);

        AgentCommission::query()->whereKey($agent->agent_commission_id)->update(['risk_balance' => 1]);

        $this->actingAs($agent)->post(
            '/api/app/agents/appliances/',
            $data + ['payment_provider' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER],
        )->assertStatus(201);
    }

    public function testProviderSaleStillValidatesDownPayment(): void {
        Queue::fake();
        $data = $this->initData();
        $this->enableVodacom();
        $this->stubVodacomC2bPayment();
        $agent = Agent::query()->latest()->first();
        $this->givePrimaryPhoneTo($data['person_id'], $data['address']['city_id']);

        // Skipping the risk-balance ceiling for provider payments must not skip the request
        // validation that shares the same middleware.
        $oversized = $data;
        $oversized['down_payment'] = 100_000;
        $oversized['payment_provider'] = MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER;
        $this->actingAs($agent)->postJson('/api/app/agents/appliances/', $oversized)->assertStatus(422);

        $missing = $data;
        unset($missing['down_payment']);
        $missing['payment_provider'] = MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER;
        $this->actingAs($agent)->postJson('/api/app/agents/appliances/', $missing)->assertStatus(422);

        $this->assertSame(0, AppliancePerson::query()->count());
    }

    private function enableVodacom(): void {
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::VODACOM_MZ_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);
    }

    private function stubVodacomC2bPayment(): void {
        $apiClient = $this->createMock(VodacomMzApiClient::class);
        $apiClient->method('c2bPayment')->willReturn([
            'output_ResponseCode' => VodacomMzApiClient::RESPONSE_SUCCESS,
            'output_ConversationID' => 'conversation-1',
            'output_TransactionID' => 'transaction-1',
        ]);
        $this->app->instance(VodacomMzApiClient::class, $apiClient);
    }

    private function givePrimaryPhoneTo(int $personId, int $cityId): void {
        $address = Address::query()->make([
            'phone' => '+258841234567',
            'city_id' => $cityId,
            'is_primary' => 1,
        ]);
        $address->owner()->associate(PersonFactory::new()->newModel()->newQuery()->findOrFail($personId))->save();
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
            'first_payment_date' => '2020-12-29T20:53:38Z',
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
