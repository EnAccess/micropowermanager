<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Services\AppliancePaymentService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePaymentServiceTest extends TestCase {
    use CreateEnvironments;

    public function testNextPayableInstallmentAmountSkipsSettledLeadingRates(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[40, 0], [495, 0], [385, 385], [385, 385]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(385.0, $service->getNextPayableInstallmentAmount($appliancePerson->rates));
    }

    public function testNextPayableInstallmentAmountUsesRemainingOfPartiallyPaidRate(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 0], [385, 100], [385, 385]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(100.0, $service->getNextPayableInstallmentAmount($appliancePerson->rates));
    }

    public function testNextPayableInstallmentAmountIsZeroWhenAllRatesSettled(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 0], [385, 0]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(0.0, $service->getNextPayableInstallmentAmount($appliancePerson->rates));
    }

    /**
     * @param array<int, array{0: int, 1: int}> $plan [rate_cost, remaining] per rate, in schedule order
     */
    private function seedPlan(array $plan): AppliancePerson {
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => 1000,
            'rate_count' => count($plan),
            'down_payment' => 0,
        ]);

        foreach ($plan as $index => [$cost, $remaining]) {
            ApplianceRate::query()->create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => $cost,
                'remaining' => $remaining,
                'remind' => 0,
                'due_date' => now()->addMonths($index + 1),
            ]);
        }

        return $appliancePerson->fresh();
    }
}
