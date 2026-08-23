<?php

namespace Tests\Feature;

use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AgentSoldApplianceWebTest extends TestCase {
    use CreateEnvironments;

    public function testUserGetsAgentsSoldApplianceList(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
        $this->createAgentSoldAppliance();
        $response = $this->actingAs($this->user)->get(sprintf('/api/agents/sold/%s', $this->agents[0]->id));
        $response->assertStatus(200);
        $this->assertEquals(1, count($response['data']));

        // the row is clickable through to /sold-appliance-detail, which resolves an
        // AppliancePerson id -- listing the agent_sold_appliances id instead opens
        // whichever unrelated sale happens to share that number
        $appliancePerson = AppliancePerson::query()->where('person_id', $response['data'][0]['person_id'])->firstOrFail();
        $this->assertEquals($appliancePerson->id, $response['data'][0]['id']);
    }

    public function testUserSellsApplianceOnBehalfOfAgent(): void {
        $this->createAgentsWithAssignedAppliance();
        $assignedAppliance = $this->assignedAppliance;
        $agentId = $assignedAppliance->agent_id;

        $response = $this->actingAs($this->user)->postJson('/api/agents/sold', [
            'agent_id' => $agentId,
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => 0,
            'tenure' => 10,
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ]);
        $response->assertStatus(201);

        $listResponse = $this->actingAs($this->user)->get(sprintf('/api/agents/sold/%s', $agentId));
        $listResponse->assertStatus(200);
        $this->assertEquals(1, count($listResponse['data']));

        $appliancePerson = AppliancePerson::query()->where('person_id', $this->person->id)->first();
        $this->assertNotNull($appliancePerson);
        $this->assertEquals('agent', $appliancePerson->creator_type);
        $this->assertEquals($agentId, $appliancePerson->creator_id);
    }

    public function testUserSellsApplianceAsEnergyServiceOnBehalfOfAgent(): void {
        $this->createAgentsWithAssignedAppliance();
        $assignedAppliance = $this->assignedAppliance;

        // an energy service sale has no installment plan, so a client may legitimately
        // send these keys as explicit nulls rather than leaving them out
        $response = $this->actingAs($this->user)->postJson('/api/agents/sold', [
            'agent_id' => $assignedAppliance->agent_id,
            'person_id' => $this->person->id,
            'payment_type' => 'energy_service',
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => 0,
            'rate_type' => null,
            'tenure' => null,
            'first_payment_date' => null,
            'minimum_payable_amount' => 500,
            'price_per_day' => 50,
        ]);
        $response->assertStatus(201);

        $appliancePerson = AppliancePerson::query()->where('person_id', $this->person->id)->firstOrFail();
        $this->assertEquals(AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, $appliancePerson->payment_type);
        $this->assertEquals('agent', $appliancePerson->creator_type);
        $this->assertEquals($assignedAppliance->agent_id, $appliancePerson->creator_id);
        $this->assertEquals(0, $appliancePerson->total_cost);
        $this->assertEquals(0, $appliancePerson->rate_count);
        $this->assertEquals(500, $appliancePerson->minimum_payable_amount);
        $this->assertEquals(50, $appliancePerson->price_per_day);

        $this->assertEquals(
            0,
            ApplianceRate::query()->where('appliance_person_id', $appliancePerson->id)->count(),
            'an energy service sale must not generate an installment schedule',
        );
    }

    public function testUserCanNotSellAnApplianceAssignedToAnotherAgent(): void {
        $this->createAgentsWithAssignedAppliance(2);
        $assignedAppliance = $this->assignedAppliance;
        $otherAgent = collect($this->agents)
            ->firstWhere(fn ($agent) => $agent->id !== $assignedAppliance->agent_id);

        $response = $this->actingAs($this->user)->postJson('/api/agents/sold', [
            'agent_id' => $otherAgent->id,
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => 0,
            'tenure' => 10,
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('agent_assigned_appliance_id');
        $this->assertEquals(0, AppliancePerson::query()->where('person_id', $this->person->id)->count());
    }

    public function testUserCanNotSellAnApplianceWithADownPaymentBiggerThanItsCost(): void {
        $this->createAgentsWithAssignedAppliance();
        $assignedAppliance = $this->assignedAppliance;

        $response = $this->actingAs($this->user)->postJson('/api/agents/sold', [
            'agent_id' => $assignedAppliance->agent_id,
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'down_payment' => $assignedAppliance->cost + 1,
            'tenure' => 10,
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('down_payment');
        $this->assertEquals(0, AppliancePerson::query()->where('person_id', $this->person->id)->count());
    }

    private function createAgentsWithAssignedAppliance(int $agentCount = 1): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent($agentCount);
        $this->createAssignedAppliances();
    }
}
