<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Log;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePersonTotalCostUpdateTest extends TestCase {
    use CreateEnvironments;

    public function testRedistributesNewTotalAcrossUnpaidRates(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 1000, rates: [200, 200, 200, 200, 200]);

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 1500, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(200);
        $appliancePerson->refresh();
        $this->assertSame(1500, (int) $appliancePerson->total_cost);

        $rates = $appliancePerson->rates()->oldest('due_date')->get();
        $this->assertSame([300, 300, 300, 300, 300], $rates->pluck('rate_cost')->map(fn ($v): int => (int) $v)->all());
        $this->assertSame([300, 300, 300, 300, 300], $rates->pluck('remaining')->map(fn ($v): int => (int) $v)->all());
    }

    public function testLastRateAbsorbsRoundingRemainder(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 900, rates: [300, 300, 300]);

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 1000, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(200);
        $rates = $appliancePerson->rates()->oldest('due_date')->get();
        $this->assertSame([333, 333, 334], $rates->pluck('rate_cost')->map(fn ($v): int => (int) $v)->all());
    }

    public function testKeepsPaidAndPartiallyPaidRatesUntouched(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 1000, rates: [200, 200, 200, 200, 200]);
        $rates = $appliancePerson->rates()->oldest('due_date')->get();
        $rates[0]->update(['remaining' => 0]);
        $rates[1]->update(['remaining' => 50]);

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 1500, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(200);

        $rates = $appliancePerson->rates()->oldest('due_date')->get();
        $this->assertSame(200, (int) $rates[0]->rate_cost);
        $this->assertSame(0, (int) $rates[0]->remaining);
        $this->assertSame(200, (int) $rates[1]->rate_cost);
        $this->assertSame(50, (int) $rates[1]->remaining);

        $paid = 200 + (200 - 50);
        $expectedShare = (int) floor((1500 - $paid) / 3);
        $this->assertSame($expectedShare, (int) $rates[2]->rate_cost);
        $this->assertSame($expectedShare, (int) $rates[3]->rate_cost);
        $this->assertSame((1500 - $paid) - $expectedShare * 2, (int) $rates[4]->rate_cost);
    }

    public function testRejectsTotalBelowAlreadyPaidAmount(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 1000, rates: [200, 200, 200, 200, 200]);
        $appliancePerson->rates()->oldest('due_date')->first()->update(['remaining' => 0]);

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 100, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.new_total_cost.0', 'New total cannot be lower than the amount already paid');
        $this->assertSame(1000, (int) $appliancePerson->fresh()->total_cost);
    }

    public function testRejectsWhenAllRatesAreFullyOrPartiallyPaid(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 600, rates: [200, 200, 200]);
        foreach ($appliancePerson->rates as $rate) {
            $rate->update(['remaining' => 50]);
        }

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 800, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.new_total_cost.0', 'All rates are paid or partially paid; edit individual rates instead');
    }

    public function testWritesAuditLogEntry(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 1000, rates: [200, 200, 200, 200, 200]);

        $this->actingAs($this->user)->put(
            "/api/appliances/person/{$appliancePerson->id}/total-cost",
            ['new_total_cost' => 1500, 'admin_id' => $this->user->id]
        )->assertStatus(200);

        $log = Log::query()
            ->where('affected_type', AppliancePerson::class)
            ->where('affected_id', $appliancePerson->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame($this->user->id, $log->user_id);
        $this->assertStringContainsString("User {$this->user->name} updated Total cost from 1000", $log->action);
        $this->assertStringContainsString('to 1500', $log->action);
    }

    public function testPerRateUpdateRejectsPaidRate(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 600, rates: [200, 200, 200]);
        $rate = $appliancePerson->rates()->oldest('due_date')->first();
        $rate->update(['remaining' => 0]);

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/rates/{$rate->id}",
            ['cost' => 200, 'newCost' => 250, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('errors.rate.0', 'Cannot modify a rate that has been paid or partially paid');
        $this->assertSame(200, (int) $rate->fresh()->rate_cost);
    }

    public function testPerRateUpdateAdjustsTotalCostByDifference(): void {
        $this->createTestData();
        $appliancePerson = $this->seedAppliance(totalCost: 1000, rates: [500, 500]);
        $rate = $appliancePerson->rates()->oldest('due_date')->first();

        $response = $this->actingAs($this->user)->put(
            "/api/appliances/rates/{$rate->id}",
            ['newCost' => 450, 'admin_id' => $this->user->id]
        );

        $response->assertStatus(200);
        $this->assertSame(450, (int) $rate->fresh()->rate_cost);
        $this->assertSame(450, (int) $rate->fresh()->remaining);
        $this->assertSame(950, (int) $appliancePerson->fresh()->total_cost);
    }

    /**
     * @param int[] $rates
     */
    private function seedAppliance(int $totalCost, array $rates): AppliancePerson {
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => $totalCost,
            'appliance_type_id' => $applianceType->id,
        ]);

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => $totalCost,
            'rate_count' => count($rates),
            'down_payment' => 0,
        ]);

        foreach ($rates as $i => $cost) {
            ApplianceRate::query()->create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $cost,
                'remaining' => $cost,
                'remind' => 0,
                'due_date' => now()->addMonths($i + 1),
            ]);
        }

        return $appliancePerson->fresh();
    }
}
