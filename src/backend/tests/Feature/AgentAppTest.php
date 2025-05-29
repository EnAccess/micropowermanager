<?php

namespace Tests\Feature;

use App\Models\AgentAssignedAppliances;
use App\Models\Person\Person;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentAppTest extends TestCase {
    use CreateEnvironments;

    public function testAgentLogsIn() {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;

        $response = $this->post('/api/app/login', [
            'email' => $agent->email,
            'password' => '123456',
        ], [
            'device-id' => '123456789',
        ]);
        $response->assertStatus(200);
        $this->assertNotNull($response->json('access_token'));
    }

    public function testAgentGetsOwnData() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->get('/api/app/me');
        $response->assertStatus(200);
        $this->assertEquals($agent->id, $response['id']);
        $this->assertEquals($agent->email, $response['email']);
        $this->assertEquals($agent->person->name, $response['name']);
    }

    public function testAgentLogsOut() {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->post('/api/app/logout');
        $response->assertStatus(200);
        $this->assertEquals($response->json('message'), 'Successfully logged out');
    }

    public function testAgentRefreshesAuthToken() {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->post('/api/app/refresh');
        $response->assertStatus(200);
    }

    public function testAgentSetsFirebaseToken() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $postData = [
            'fire_base_token' => '123456789',
        ];
        $response = $this->actingAs($this->agent)->post('/api/app/agents/firebase', $postData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['fire_base_token'], $postData['fire_base_token']);
    }

    public function testAgentGetsBalance() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->get('/api/app/agents/balance');
        $response->assertStatus(200);
        $this->assertEquals($agent->balance, $response->getContent());
    }

    public function testAgentGetsCustomers() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $personCount = 10;
        $this->createPerson($personCount);
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->get('/api/app/agents/customers');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $personCount);
    }

    public function testAgentSearchesInCustomers() {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createMeterType();
        $this->createMeterTariff();
        $personCount = 10;
        $this->createPerson($personCount);
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $person = Person::query()->where('is_customer', 1)->first();
        $response = $this->actingAs($this->agent)->get(sprintf('/api/app/agents/customers/search?q=%s', $person->name));
        $response->assertStatus(200);
        $this->assertNotNull($response['data']);
        $this->assertEquals($response['data'][0]['name'], $person->name);
    }

    public function testAgentGetsCustomersPaymentFlow() {
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
        $response = $this->actingAs($this->agent)->get(sprintf('/api/app/agents/customers/graph/%s', 'D'));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
    }

    public function testAgentGetsCustomerPaymentFlowByCustomerId() {
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
            $this->actingAs($this->agent)->get(sprintf('/api/app/agents/customers/%s/graph/%s', $customerId, 'D'));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
    }

    public function testAgentGetsCustomersTransactions() {
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
        $response = $this->actingAs($this->agent)->get('/api/app/agents/transactions');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentTransactionCount);
    }

    public function testAgentGetsCustomerTransactionsByCustomerId() {
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
        $response = $this->actingAs($this->agent)->get(sprintf('/api/app/agents/transactions/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $agentTransactionCount);
    }

    public function testAgentGetsSoldAppliances() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->agent)->get('/api/app/agents/appliances');
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
        $this->assertEquals(count($response['data']), 1);
    }

    public function testAgentGetsCustomerSoldAppliancesByCustomerId() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->agent)->get(sprintf('/api/app/agents/appliances/%s', $this->person->id));
        $response->assertStatus(200);
        $this->assertNotNull($response->getContent());
        $this->assertEquals(count($response['data']), 1);
    }

    public function testAgentSalesAppliances() {
        $this->createTestData();
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

        $response = $this->actingAs($this->agent)->post('/api/app/agents/appliances', $postData);
        $response->assertStatus(201);
    }

    public function testAgentGetsAssignedAppliances() {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $assignedApplianceCount = 2;
        $this->createAssignedAppliances($assignedApplianceCount);
        $assignedAppliance = AgentAssignedAppliances::query()->first();
        $response = $this->actingAs($this->agent)->get('/api/app/agents/applianceTypes');
        $response->assertStatus(200);
        $this->assertEquals(count($response['data']), $assignedApplianceCount);
    }

    public function testAgentGetsApplicationDashboardBoxes() {
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
        $response = $this->actingAs($this->agent)->get('/api/app/agents/dashboard/boxes');
        $response->assertStatus(200);
        $this->assertEquals(array_key_exists('balance', $response['data']), true);
        $this->assertEquals(array_key_exists('profit', $response['data']), true);
        $this->assertEquals(array_key_exists('dept', $response['data']), true);
        $this->assertEquals(array_key_exists('average', $response['data']), true);
        $this->assertEquals(array_key_exists('since', $response['data']), true);
    }

    public function testAgentGetsApplicationDashboardGraphValues() {
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
        $response = $this->actingAs($this->agent)->get('/api/app/agents/dashboard/graph');
        $response->assertStatus(200);
    }

    public function testAgentGetsApplicationDashboardWeeklyRevenues() {
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
        $response = $this->actingAs($this->agent)->get('/api/app/agents/dashboard/revenue');
        $response->assertStatus(200);
    }

    public function actingAs($user, $driver = null) {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
