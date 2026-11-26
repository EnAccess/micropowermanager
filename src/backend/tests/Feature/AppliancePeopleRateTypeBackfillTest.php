<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RateType;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use Database\Factories\ApplianceFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePeopleRateTypeBackfillTest extends TestCase {
    use CreateEnvironments;

    public function testItReadsTheRateTypeFromTheGapBetweenTheLastTwoRates(): void {
        $this->createTestData();
        $weekly = $this->seedLegacyPlan(['2026-01-01', '2026-01-08', '2026-01-15']);
        $monthly = $this->seedLegacyPlan(['2026-01-01', '2026-02-01', '2026-03-01']);
        $rescheduledToMonthly = $this->seedLegacyPlan(['2026-01-01', '2026-01-08', '2026-02-08', '2026-03-08']);
        $rescheduledToWeekly = $this->seedLegacyPlan(['2026-01-01', '2026-02-01', '2026-02-08', '2026-02-15']);
        $weeklyBehindADownPayment = $this->seedLegacyPlan(['2026-01-01', '2026-01-08']);
        $singleRate = $this->seedLegacyPlan(['2026-01-01']);
        $energyService = $this->seedLegacyPlan([], AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE);

        $this->runBackfill();

        $this->assertSame(RateType::Weekly, $weekly->refresh()->rate_type);
        $this->assertSame(RateType::Monthly, $monthly->refresh()->rate_type);
        $this->assertSame(RateType::Monthly, $rescheduledToMonthly->refresh()->rate_type);
        $this->assertSame(RateType::Weekly, $rescheduledToWeekly->refresh()->rate_type);
        $this->assertSame(RateType::Weekly, $weeklyBehindADownPayment->refresh()->rate_type);
        $this->assertSame(RateType::Monthly, $singleRate->refresh()->rate_type);
        $this->assertNull($energyService->refresh()->rate_type);
    }

    public function testItLeavesAStoredRateTypeAlone(): void {
        $this->createTestData();
        $appliancePerson = $this->seedLegacyPlan(['2026-01-01', '2026-02-01']);
        $appliancePerson->update(['rate_type' => RateType::Weekly]);

        $this->runBackfill();

        $this->assertSame(RateType::Weekly, $appliancePerson->refresh()->rate_type);
    }

    private function runBackfill(): void {
        $migration = require database_path('migrations/tenant/2026_11_24_000000_add_rate_type_to_appliance_people_table.php');
        $migration->backfill();
    }

    /**
     * @param list<string> $dueDates
     */
    private function seedLegacyPlan(array $dueDates, string $paymentType = AppliancePerson::PAYMENT_TYPE_INSTALLMENT): AppliancePerson {
        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => ApplianceFactory::new()->create(['appliance_type_id' => ApplianceTypeFactory::new()->create()->id])->id,
            'payment_type' => $paymentType,
            'rate_count' => count($dueDates),
            'rate_type' => null,
        ]);

        foreach ($dueDates as $dueDate) {
            ApplianceRate::query()->create([
                'appliance_person_id' => $appliancePerson->id,
                'rate_cost' => 100,
                'remaining' => 100,
                'remind' => 0,
                'due_date' => $dueDate,
            ]);
        }

        return $appliancePerson;
    }
}
