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

    public function testNextPayableInstallmentCostIgnoresPartialPaymentsOnThatRate(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 0], [385, 100], [385, 385]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(385.0, $service->getNextPayableInstallmentCost($appliancePerson->rates));
    }

    public function testNextPayableInstallmentCostSkipsSettledLeadingRates(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[40, 0], [495, 0], [385, 385], [385, 385]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(385.0, $service->getNextPayableInstallmentCost($appliancePerson->rates));
    }

    public function testNextPayableInstallmentCostIsZeroWhenAllRatesSettled(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 0], [385, 0]]);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(0.0, $service->getNextPayableInstallmentCost($appliancePerson->rates));
    }

    /**
     * Guards the SHS token day math (chargeAmount = amount / (installmentCost /
     * dayDifference)): a rate that is nearly paid off must not collapse the per-day
     * price and hand the customer weeks of energy for one installment's worth of money.
     */
    public function testTokenDayCountIsUnaffectedByPartialPaymentOnCurrentRate(): void {
        $this->createTestData();
        $weeklyDueDates = ['2026-09-01', '2026-09-08', '2026-09-15'];
        $fullyOutstanding = $this->seedPlan([[700, 700], [700, 700], [700, 700]], $weeklyDueDates);
        $almostSettled = $this->seedPlan([[700, 35], [700, 700], [700, 700]], $weeklyDueDates);

        $service = resolve(AppliancePaymentService::class);
        $tokenDaysFor = function (AppliancePerson $appliancePerson) use ($service): float {
            $rates = $appliancePerson->rates;
            $costPerDay = $service->getNextPayableInstallmentCost($rates)
                / $service->getDayDifferenceBetweenTwoInstallments($rates);

            return ceil(800 / $costPerDay);
        };

        $this->assertSame(8.0, $tokenDaysFor($fullyOutstanding));
        $this->assertSame(8.0, $tokenDaysFor($almostSettled));
    }

    public function testDayDifferenceStaysPositiveWhenADownPaymentPredatesTheSchedule(): void {
        $this->createTestData();
        // The down payment row is written after the schedule, so it trails the
        // installments by id while preceding them by due date.
        $appliancePerson = $this->seedPlan(
            [[385, 385], [385, 385], [40, 0]],
            ['2026-09-01', '2026-10-01', '2026-07-30'],
        );

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(30.0, $service->getDayDifferenceBetweenTwoInstallments($appliancePerson->rates));
    }

    public function testDayDifferenceFallsBackToDefaultForShortSchedules(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 385], [385, 385]], ['2026-09-01', '2026-09-08']);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(
            (float) AppliancePaymentService::DEFAULT_DAY_DIFFERENCE_BETWEEN_INSTALLMENTS,
            $service->getDayDifferenceBetweenTwoInstallments($appliancePerson->rates),
        );
    }

    /**
     * @param array<int, array{0: int, 1: int}> $plan     [rate_cost, remaining] per rate, in schedule order
     * @param array<int, string>|null           $dueDates due date per rate; defaults to a monthly schedule
     */
    private function seedPlan(array $plan, ?array $dueDates = null): AppliancePerson {
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
                'due_date' => $dueDates[$index] ?? now()->addMonths($index + 1),
            ]);
        }

        return $appliancePerson->fresh();
    }
}
