<?php

namespace Tests\Feature;

use App\Models\AgentAssignedAppliances;
use App\Models\AppliancePerson;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceRateFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePaymentPlanEnergyAsAServiceTest extends TestCase {
    use CreateEnvironments;

    private function setUpAgentEnvironment(): void {
        $this->createTestData();
        $this->createCluster();
        $this->createMiniGrid();
        $this->createCity();
        $this->createAgentCommission();
        $this->createAgent();
        $this->createAssignedAppliances();
    }

    private function createEaaSAppliancePerson(int $minimumPayableAmount = 0): AppliancePerson {
        $this->createTestData();
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create(['paygo_enabled' => false]);
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        return AppliancePersonFactory::new()->create([
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => $minimumPayableAmount ?: null,
            'price_per_day' => 100,
            'total_cost' => 0,
            'rate_count' => 0,
            'first_payment_date' => null,
            'creator_type' => 'user',
            'creator_id' => $this->user->id,
        ]);
    }

    public function testAgentSellsEaaSApplianceSuccessfully(): void {
        $this->setUpAgentEnvironment();
        $assignedAppliance = AgentAssignedAppliances::query()->first();

        $response = $this->actingAs($this->agent)->post('/api/app/agents/appliances', [
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => 500,
            'price_per_day' => 100,
            'down_payment' => 0,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('appliance_people', [
            'person_id' => $this->person->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
        ], 'tenant');
    }

    public function testAgentEaaSSaleDoesNotRequireTenureOrFirstPaymentDate(): void {
        $this->setUpAgentEnvironment();
        $assignedAppliance = AgentAssignedAppliances::query()->first();

        $response = $this->actingAs($this->agent)->post('/api/app/agents/appliances', [
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'down_payment' => 0,
        ]);

        $response->assertStatus(201);
    }

    public function testAgentEaaSSaleStoresMinimumPayableAmountAndPricePerDay(): void {
        $this->setUpAgentEnvironment();
        $assignedAppliance = AgentAssignedAppliances::query()->first();

        $response = $this->actingAs($this->agent)->post('/api/app/agents/appliances', [
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => 300,
            'price_per_day' => 50,
            'down_payment' => 0,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('appliance_people', [
            'person_id' => $this->person->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => 300,
            'price_per_day' => 50,
        ], 'tenant');
    }

    public function testAgentEaaSSaleDoesNotCreateInstallmentRates(): void {
        $this->setUpAgentEnvironment();
        $assignedAppliance = AgentAssignedAppliances::query()->first();

        $this->actingAs($this->agent)->post('/api/app/agents/appliances', [
            'person_id' => $this->person->id,
            'agent_assigned_appliance_id' => $assignedAppliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => 500,
            'down_payment' => 0,
        ]);

        $appliancePerson = AppliancePerson::query()
            ->where('person_id', $this->person->id)
            ->where('payment_type', AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE)
            ->first();

        $this->assertNotNull($appliancePerson);
        $this->assertEquals(0, $appliancePerson->rates()->count());
    }

    public function testWebUserCreatesEaaSAppliancePerson(): void {
        $this->createTestData();
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = ApplianceFactory::new()->create(['appliance_type_id' => $applianceType->id]);

        $response = $this->actingAs($this->user)->post(
            sprintf('/api/appliances/person/%s/people/%s', $appliance->id, $person->id),
            [
                'id' => $appliance->id,
                'person_id' => $person->id,
                'user_id' => $this->user->id,
                'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
                'minimum_payable_amount' => 500,
                'price_per_day' => 100,
                'down_payment' => 0,
            ]
        );

        $response->assertStatus(201);

        $this->assertDatabaseHas('appliance_people', [
            'person_id' => $person->id,
            'appliance_id' => $appliance->id,
            'payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE,
            'minimum_payable_amount' => 500,
            'price_per_day' => 100,
        ], 'tenant');
    }

    public function testPaymentIsAcceptedForEaaSAppliance(): void {
        Queue::fake();
        $appliancePerson = $this->createEaaSAppliancePerson(minimumPayableAmount: 0);

        $response = $this->actingAs($this->user)->post(
            sprintf('/api/appliances/payment/%s', $appliancePerson->id),
            ['amount' => 500]
        );

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.transaction_id'));
    }

    public function testPaymentWithMinimumAmountIsAcceptedForEaaSAppliance(): void {
        Queue::fake();
        $appliancePerson = $this->createEaaSAppliancePerson(minimumPayableAmount: 300);

        $response = $this->actingAs($this->user)->post(
            sprintf('/api/appliances/payment/%s', $appliancePerson->id),
            ['amount' => 300]
        );

        $response->assertStatus(200);
        $this->assertNotNull($response->json('data.transaction_id'));
    }

    public function testPaymentBelowMinimumIsRejectedForEaaSAppliance(): void {
        Queue::fake();
        $this->withoutExceptionHandling();
        $appliancePerson = $this->createEaaSAppliancePerson(minimumPayableAmount: 500);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment amount can not be less than minimum payable amount (500)');

        $this->actingAs($this->user)->post(
            sprintf('/api/appliances/payment/%s', $appliancePerson->id),
            ['amount' => 100]
        );
    }

    public function testZeroAmountPaymentIsRejectedForEaaSAppliance(): void {
        Queue::fake();
        $this->withoutExceptionHandling();
        $appliancePerson = $this->createEaaSAppliancePerson();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment amount can not smaller than zero');

        $this->actingAs($this->user)->post(
            sprintf('/api/appliances/payment/%s', $appliancePerson->id),
            ['amount' => 0]
        );
    }

    public function testGetRatesForEaaSAppliancePerson(): void {
        $appliancePerson = $this->createEaaSAppliancePerson();

        ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 500,
            'remaining' => 0,
            'due_date' => now()->toDateString(),
        ]);

        ApplianceRateFactory::new()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 300,
            'remaining' => 0,
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->user)->get(
            sprintf('/api/appliances/person/%s/rates', $appliancePerson->id)
        );

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }
}
