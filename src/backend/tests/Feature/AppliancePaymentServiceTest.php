<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RateType;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Services\AppliancePaymentService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Support\Carbon;
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
        $fullyOutstanding = $this->seedPlan([[700, 700], [700, 700], [700, 700]], $weeklyDueDates, RateType::Weekly);
        $almostSettled = $this->seedPlan([[700, 35], [700, 700], [700, 700]], $weeklyDueDates, RateType::Weekly);

        $service = resolve(AppliancePaymentService::class);
        $tokenDaysFor = fn (AppliancePerson $appliancePerson): float => ceil(800 / $service->getDailyPrice($appliancePerson));

        $this->assertSame(8.0, $tokenDaysFor($fullyOutstanding));
        $this->assertSame(8.0, $tokenDaysFor($almostSettled));
    }

    public function testAWeeklyInstallmentCoversSevenDays(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[385, 385], [385, 385]], ['2026-09-01', '2026-09-08'], RateType::Weekly);

        $this->assertSame(7.0, resolve(AppliancePaymentService::class)->getInstallmentPeriodDays($appliancePerson));
    }

    public function testAMonthlyInstallmentCoversTheMonthItFallsDueIn(): void {
        $this->createTestData();
        $dueInSeptember = $this->seedPlan([[300, 300], [300, 300]], ['2026-09-01', '2026-10-01'], RateType::Monthly);
        $dueInOctober = $this->seedPlan([[300, 0], [300, 300]], ['2026-09-01', '2026-10-01'], RateType::Monthly);

        $service = resolve(AppliancePaymentService::class);

        $this->assertSame(30.0, $service->getInstallmentPeriodDays($dueInSeptember));
        $this->assertSame(31.0, $service->getInstallmentPeriodDays($dueInOctober));
    }

    public function testASingleInstallmentBehindADownPaymentCoversItsOwnPeriod(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[680, 0], [160, 160]], ['2026-11-23', '2026-12-23'], RateType::Weekly);

        $this->assertSame(7.0, resolve(AppliancePaymentService::class)->getInstallmentPeriodDays($appliancePerson));
    }

    public function testARescheduledPlanFollowsItsCurrentRateType(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan(
            [[400, 0], [400, 0], [400, 0], [300, 300], [300, 300]],
            ['2026-05-01', '2026-06-01', '2026-07-01', '2026-07-08', '2026-07-15'],
            RateType::Weekly,
        );

        $this->assertSame(7.0, resolve(AppliancePaymentService::class)->getInstallmentPeriodDays($appliancePerson));
    }

    public function testASettledPlanMeasuresItsPeriodFromToday(): void {
        $this->createTestData();
        $this->travelTo(Carbon::parse('2027-02-10'));
        $appliancePerson = $this->seedPlan([[300, 0], [300, 0]], ['2026-12-01', '2027-01-01'], RateType::Monthly);

        $this->assertSame(28.0, resolve(AppliancePaymentService::class)->getInstallmentPeriodDays($appliancePerson));
    }

    public function testAnInstallmentPlanWithoutARateTypeIsRejected(): void {
        $this->createTestData();
        $appliancePerson = $this->seedPlan([[300, 300], [300, 300]], ['2026-09-01', '2026-10-01'], null);

        $this->expectException(\LogicException::class);

        resolve(AppliancePaymentService::class)->getInstallmentPeriodDays($appliancePerson);
    }

    /**
     * @param array<int, array{0: int, 1: int}> $plan     [rate_cost, remaining] per rate, in schedule order
     * @param array<int, string>|null           $dueDates due date per rate; defaults to a monthly schedule
     */
    private function seedPlan(array $plan, ?array $dueDates = null, ?RateType $rateType = RateType::Monthly): AppliancePerson {
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
            'rate_type' => $rateType,
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
