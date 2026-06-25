<?php

namespace Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\AgentAssignedAppliances;
use App\Models\City;
use App\Models\MainSettings;
use App\Models\Person\Person;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentAppTest extends TestCase {
    use CreateEnvironments;

    public function testAgentLogsIn(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;

        $response = $this->postJson('/api/app/login', [
            'email' => $agent->email,
            'password' => '123456',
        ], [
            'device-id' => '123456789',
        ]);
        $response->assertStatus(200);
        $this->assertNotNull($response->json('access_token'));
    }

    public function testAgentGetsOwnData(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->getJson('/api/app/me');
        $response->assertStatus(200);
        $this->assertEquals($agent->id, $response['agent']['id']);
        $this->assertEquals($agent->email, $response['agent']['email']);
        $this->assertEquals($agent->person->id, $response['agent']['person_id']);
    }

    public function testAgentLoginResponseIncludesTenantSettings(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->setMainSettings([
            'currency' => 'TZS',
            'country' => 'Tanzania',
            'language' => 'sw',
            'company_name' => 'Acme Mini-Grid',
        ]);

        $response = $this->postJson('/api/app/login', [
            'email' => $this->agent->email,
            'password' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('settings.currency', 'TZS');
        $response->assertJsonPath('settings.country', 'Tanzania');
        $response->assertJsonPath('settings.language', 'sw');
        $response->assertJsonPath('settings.company_name', 'Acme Mini-Grid');
    }

    public function testAgentMeResponseIncludesTenantSettings(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->setMainSettings([
            'currency' => 'TZS',
            'country' => 'Tanzania',
            'language' => 'sw',
            'company_name' => 'Acme Mini-Grid',
        ]);

        $response = $this->actingAs($this->agent)->getJson('/api/app/me');

        $response->assertStatus(200);
        $response->assertJsonPath('settings.currency', 'TZS');
        $response->assertJsonPath('settings.country', 'Tanzania');
        $response->assertJsonPath('settings.language', 'sw');
        $response->assertJsonPath('settings.company_name', 'Acme Mini-Grid');
    }

    public function testAgentAuthSettingsAreNullWhenMainSettingsAreMissing(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        MainSettings::query()->delete();

        $response = $this->postJson('/api/app/login', [
            'email' => $this->agent->email,
            'password' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('settings', null);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function setMainSettings(array $attributes): MainSettings {
        $settings = MainSettings::query()->first();
        if ($settings instanceof MainSettings) {
            $settings->update($attributes);

            return $settings->fresh();
        }

        return MainSettings::factory()->create($attributes);
    }

    public function testAgentLogsOut(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $response = $this->actingAs($this->agent)->postJson('/api/app/logout');
        $response->assertStatus(200);
        $this->assertEquals($response->json('message'), 'Successfully logged out');
    }

    public function testAgentRefreshesAuthToken(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $response = $this->actingAs($this->agent)->postJson('/api/app/refresh');
        $response->assertStatus(200);
    }

    public function testAgentSetsFirebaseToken(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $postData = [
            'fire_base_token' => '123456789',
        ];
        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/firebase', $postData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['fire_base_token'], $postData['fire_base_token']);
    }

    public function testAgentGetsBalance(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/balance');
        $response->assertStatus(200);
        $this->assertEquals($agent->balance, $response->getContent());
    }

    public function testAgentGetsCustomers(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createAgentCommission();
        $this->createAgent();
        $personCount = 10;
        $this->createPerson($personCount);
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/customers');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $personCount);
    }

    public function testAgentSearchesInCustomers(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createAgentCommission();
        $this->createAgent();
        $personCount = 10;
        $this->createPerson($personCount);
        $person = Person::query()->where('is_customer', 1)->first();
        $response = $this->actingAs($this->agent)->getJson(sprintf('/api/app/agents/customers/search?term=%s', $person->name));
        $response->assertStatus(200);
        $this->assertNotNull($response['data']);
        $returnedIds = array_column($response['data'], 'id');
        $this->assertContains($person->id, $returnedIds);
    }

    public function testAgentGetsCustomersPaymentFlow(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson(sprintf('/api/app/agents/customers/graph/%s', 'D'));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
    }

    public function testAgentGetsCustomerPaymentFlowByCustomerId(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 1;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $customerId = $this->person->id;
        $response =
            $this->actingAs($this->agent)->getJson(sprintf('/api/app/agents/customers/%s/graph/%s', $customerId, 'D'));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
    }

    public function testAgentGetsCustomersTransactions(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/transactions');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentTransactionCount);
    }

    public function testAgentGetsCustomerTransactionsByCustomerId(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson(sprintf('/api/app/agents/transactions/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentTransactionCount);
    }

    public function testAgentGetsSoldAppliances(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/appliances');
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
        $this->assertEquals(count($response['data']), 1);
    }

    public function testAgentGetsCustomerSoldAppliancesByCustomerId(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->agent)->getJson(sprintf('/api/app/agents/appliances/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
        $this->assertEquals(count($response['data']), 1);
    }

    public function testAgentSalesAppliances(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $assignedAppliance = AgentAssignedAppliances::query()->first();
        $postData = [
            'down_payment' => 0,
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'tenure' => 10,
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ];
        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/appliances', $postData);
        $response->assertStatus(201);
    }

    public function testAgentGetsAssignedAppliances(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $assignedApplianceCount = 2;
        $this->createAssignedAppliances($assignedApplianceCount);
        AgentAssignedAppliances::query()->first();
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/appliance_types');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $assignedApplianceCount);
    }

    public function testAgentGetsApplicationDashboardBoxes(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/dashboard/boxes');
        $response->assertStatus(200);
        $this->assertEquals(array_key_exists('balance', $response['data']), true);
        $this->assertEquals(array_key_exists('profit', $response['data']), true);
        $this->assertEquals(array_key_exists('dept', $response['data']), true);
        $this->assertEquals(array_key_exists('average', $response['data']), true);
        $this->assertEquals(array_key_exists('since', $response['data']), true);
    }

    public function testAgentGetsApplicationDashboardGraphValues(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/dashboard/graph');
        $response->assertStatus(200);
    }

    public function testAgentRegistersCustomer(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $postData = [
            'name' => 'Jane',
            'surname' => 'Doe',
            'phone' => '+14155550100',
            'city_id' => $this->city->id,
            'geo_points' => '52.5200,13.4050',
        ];
        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/customers', $postData);
        $response->assertStatus(201);
        $this->assertEquals('Jane', $response['data']['name']);
        $this->assertEquals(1, $response['data']['is_customer']);

        $person = Person::query()->where('name', 'Jane')->where('surname', 'Doe')->firstOrFail();
        $address = $person->addresses()->where('is_primary', 1)->firstOrFail();
        $this->assertEquals($this->city->id, $address->city_id);
        $this->assertEquals('+14155550100', $address->phone);
        $this->assertEquals('52.5200,13.4050', $address->geo->points);
    }

    public function testAgentCannotRegisterCustomerWithDuplicatePhone(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $postData = [
            'name' => 'Jane',
            'surname' => 'Doe',
            'phone' => '+14155550100',
            'city_id' => $this->city->id,
        ];
        $this->actingAs($this->agent)->postJson('/api/app/agents/customers', $postData)->assertStatus(201);
        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/customers', $postData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['phone']);
    }

    public function testAgentCannotRegisterCustomerInForeignMiniGrid(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid(2);
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $foreignMiniGrid = collect($this->miniGrids)
            ->first(fn ($miniGrid): bool => $miniGrid->id !== $this->agent->mini_grid_id);
        $foreignCity = City::query()->create([
            'name' => 'Foreignville',
            'country_id' => 1,
            'mini_grid_id' => $foreignMiniGrid->id,
        ]);

        $postData = [
            'name' => 'Jane',
            'surname' => 'Doe',
            'phone' => '+14155550100',
            'city_id' => $foreignCity->id,
        ];
        $response = $this->actingAs($this->agent)->postJson('/api/app/agents/customers', $postData);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['city_id']);
    }

    public function testAgentRecordsCashTransaction(): void {
        Queue::fake();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $postData = [
            'device_serial' => 'MTR-TX-001',
            'amount' => 500,
        ];
        $response = $this->actingAs($this->agent)
            ->postJson('/api/app/agents/transactions', $postData, ['device-id' => $this->agent->mobile_device_id]);
        $response->assertStatus(200);

        $transaction = Transaction::query()->where('message', 'MTR-TX-001')->firstOrFail();
        $this->assertSame(500, (int) $transaction->amount);
        $this->assertSame('Agent-'.$this->agent->id, $transaction->sender);
        $this->assertSame('energy', $transaction->type);
        $this->assertSame('agent_transaction', $transaction->original_transaction_type);

        $agentTransaction = AgentTransaction::query()->where('agent_id', $this->agent->id)->firstOrFail();
        $this->assertSame($agentTransaction->id, (int) $transaction->original_transaction_id);

        Queue::assertPushed(ProcessPayment::class);
    }

    public function testAgentTransactionRejectedWhenAmountExceedsRiskBalance(): void {
        Queue::fake();
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();

        $postData = [
            'device_serial' => 'MTR-TX-002',
            'amount' => 999_999_999,
        ];
        $response = $this->actingAs($this->agent)
            ->postJson('/api/app/agents/transactions', $postData, ['device-id' => $this->agent->mobile_device_id]);
        $response->assertStatus(403);
        $response->assertJson(['message' => 'Risk balance exceeded']);
        $this->assertSame(0, Transaction::query()->where('message', 'MTR-TX-002')->count());
        Queue::assertNotPushed(ProcessPayment::class);
    }

    public function testAgentGetsApplicationDashboardWeeklyRevenues(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createPerson();
        $this->createMeterType();
        $this->createMeterTariff();
        $this->createMeterManufacturer();
        $this->createConnectionGroup();
        $this->createConnectionType();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createPerson();
        $agentTransactionCount = 3;
        $amount = 100;
        $agentId = $this->agents[0]->id;
        $this->createAgentTransaction($agentTransactionCount, $amount, $agentId);
        $response = $this->actingAs($this->agent)->getJson('/api/app/agents/dashboard/revenue');
        $response->assertStatus(200);
    }
}
