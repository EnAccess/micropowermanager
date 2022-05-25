<?php

namespace Tests\Feature;

use App\Models\AgentAssignedAppliances;
use App\Models\Person\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class AgentAppTest extends TestCase
{
    use CreateEnvironments;

    public function test_agent_logs_in()
    {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;

        $response = $this->post('/api/app/login', [
            'email' => $agent->email,
            'password' => '123456'
        ], [
            'device-id' => '123456789',
        ]);
        $response->assertStatus(200);
        $this->assertNotNull($response->json('access_token'));
    }

    public function test_agent_gets_own_data()
    {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->get('/api/app/me');
        $response->assertStatus(200);
        $this->assertEquals($agent->id, $response['id']);
        $this->assertEquals($agent->email, $response['email']);
        $this->assertEquals($agent->name, $response['name']);

    }

    public function test_agent_logs_out()
    {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->post('/api/app/logout');
        $response->assertStatus(200);
        $this->assertEquals($response->json('message'), 'Successfully logged out');

    }

    public function test_agent_refreshes_auth_token()
    {
        $this->createTestData();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->post('/api/app/refresh');
        $response->assertStatus(200);

    }

    public function test_agent_sets_firebase_token()
    {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $postData = [
            'fire_base_token' => '123456789'
        ];
        $response = $this->actingAs($this->agent)->post('/api/app/agents/firebase', $postData);
        $response->assertStatus(200);
        $this->assertEquals($response['data']['fire_base_token'], $postData['fire_base_token']);
    }

    public function test_agent_gets_balance()
    {
        $this->createTestData();
        $this->createAgentCommission();
        $this->createAgent();
        $agent = $this->agent;
        $response = $this->actingAs($this->agent)->get('/api/app/agents/balance');
        $response->assertStatus(200);
        $this->assertEquals($agent->balance, $response->getContent());


    }

    public function test_agent_gets_customers()
    {
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

    public function test_agent_searches_in_customers()
    {
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

    public function test_agent_gets_customers_payment_flow()
    {
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

    public function test_agent_gets_customer_payment_flow_by_customer_id()
    {
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

    public function test_agent_gets_customers_transactions()
    {
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

    public function test_agent_gets_customer_transactions_by_customer_id()
    {
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

    public function test_agent_gets_sold_appliances()
    {
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

    public function test_agent_gets_customer_sold_appliances_by_customer_id()
    {
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

    public function test_agent_sales_appliances()
    {
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

    public function test_agent_gets_assigned_appliances()
    {
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

    public function test_agent_gets_application_dashboard_boxes()
    {
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
        $this->assertEquals(array_key_exists('balance',$response['data']), true);
        $this->assertEquals(array_key_exists('profit',$response['data']), true);
        $this->assertEquals(array_key_exists('dept',$response['data']), true);
        $this->assertEquals(array_key_exists('average',$response['data']), true);
        $this->assertEquals(array_key_exists('since',$response['data']), true);

    }

    public function test_agent_gets_application_dashboard_graph_values()
    {
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

    public function test_agent_gets_application_dashboard_weekly_revenues()
    {
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

    public function actingAs($user, $driver = null)
    {
        $token = JWTAuth::fromUser($user);
        $this->withHeader('Authorization', "Bearer {$token}");
        parent::actingAs($user);

        return $this;
    }
}
