<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RateType;
use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Transaction\Transaction;
use Carbon\Carbon;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePersonControllerDownPaymentTest extends TestCase {
    use CreateEnvironments;

    public function testTheDownPaymentBecomesAnOutstandingRateDueOnTheDayOfTheSale(): void {
        $this->createTestData();
        Queue::fake();

        $response = $this->sell(['cost' => 1000, 'rate' => 5, 'down_payment' => 200]);

        $response->assertStatus(200);
        $appliancePersonId = $response->json('data.appliance_person.id');

        $rates = ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->oldest('due_date')->get();
        $this->assertCount(6, $rates);

        $downPaymentRate = $rates->first();
        $this->assertSame(200, $downPaymentRate->rate_cost);
        $this->assertSame(200, $downPaymentRate->remaining);
        $this->assertTrue(Carbon::parse($downPaymentRate->due_date)->isToday());

        $this->assertSame(800, $rates->skip(1)->sum('rate_cost'));
        $this->assertSame(0, Transaction::query()->count());
        Queue::assertNothingPushed();
    }

    public function testTheDownPaymentRateIsPaidThroughTheAppliancePaymentEndpoint(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePersonId = $this->sell(['cost' => 1000, 'rate' => 5, 'down_payment' => 200])
            ->json('data.appliance_person.id');

        $response = $this->actingAs($this->user)->post("/api/appliances/payment/{$appliancePersonId}", [
            'amount' => 200,
            'payment_provider' => 'cash',
        ]);

        $response->assertStatus(200);
        $this->assertSame(200.0, (float) Transaction::query()->findOrFail($response->json('data.transaction_id'))->amount);
        Queue::assertPushed(ProcessPayment::class);
    }

    public function testTheOutstandingDownPaymentRateCannotBeModified(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePersonId = $this->sell(['cost' => 1000, 'rate' => 5, 'down_payment' => 200])
            ->json('data.appliance_person.id');
        $rates = ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->oldest('due_date')->get();

        $this->actingAs($this->user)
            ->putJson("/api/appliances/rates/{$rates->first()->id}", ['newCost' => 150, 'admin_id' => $this->user->id])
            ->assertStatus(422);
        $this->assertSame(200, $rates->first()->refresh()->rate_cost);

        $this->actingAs($this->user)
            ->putJson("/api/appliances/rates/{$rates->get(1)->id}", ['newCost' => 150, 'admin_id' => $this->user->id])
            ->assertStatus(200);
    }

    public function testASaleWithoutDownPaymentCreatesOnlyTheSchedule(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePersonId = $this->sell(['cost' => 1000, 'rate' => 5, 'down_payment' => 0])
            ->json('data.appliance_person.id');

        $rates = ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->get();
        $this->assertCount(5, $rates);
        $this->assertSame(1000, $rates->sum('rate_cost'));
        $this->assertFalse($rates->contains(fn (ApplianceRate $rate): bool => Carbon::parse($rate->due_date)->isToday()));
    }

    public function testAWeeklyPlanStartsWhenTheDownPaymentsDaysRunOut(): void {
        $this->createTestData();
        Queue::fake();

        // 680 at 160 a week buys 29.75 days, vended as 30.
        $sale = $this->sell(['cost' => 2600, 'rate' => 12, 'rate_type' => 'weekly', 'down_payment' => 680]);

        $this->assertSame(
            array_map(fn (int $week): string => Carbon::today()->addDays(30 + 7 * $week)->toDateString(), range(0, 11)),
            $this->installmentDueDates($sale),
        );
        $appliancePerson = AppliancePerson::query()->findOrFail($sale->json('data.appliance_person.id'));
        $this->assertSame(Carbon::today()->addDays(30)->toDateString(), Carbon::parse($appliancePerson->first_payment_date)->toDateString());
        $this->assertSame(RateType::Weekly, $appliancePerson->rate_type);
    }

    public function testADownPaymentThatBuysMoreDaysThanTheLimitStartsTheInstallmentsAtTheLimit(): void {
        $this->createTestData();
        Queue::fake();
        MainSettings::query()->firstOrFail()->update(['down_payment_max_token_days' => 14]);

        $sale = $this->sell(['cost' => 2600, 'rate' => 12, 'rate_type' => 'weekly', 'down_payment' => 680]);

        $this->assertSame(Carbon::today()->addDays(14)->toDateString(), $this->installmentDueDates($sale)[0]);
    }

    public function testADownPaymentOfLessThanAWeekLeavesTheFirstInstallmentAWeekAway(): void {
        $this->createTestData();
        Queue::fake();

        // 100 at 180 a week buys 4 days.
        $sale = $this->sell(['cost' => 1000, 'rate' => 5, 'rate_type' => 'weekly', 'down_payment' => 100]);

        $this->assertSame(Carbon::today()->addWeek()->toDateString(), $this->installmentDueDates($sale)[0]);
    }

    public function testAMonthlyPlanStartsAboutAMonthAfterTheDownPayment(): void {
        $this->createTestData();
        Queue::fake();

        // 200 at 160 a month buys more than a month, held to the 30-day default limit.
        $sale = $this->sell(['cost' => 1000, 'rate' => 5, 'rate_type' => 'monthly', 'down_payment' => 200]);

        $this->assertSame(
            Carbon::today()->addMonth()->max(Carbon::today()->addDays(30))->toDateString(),
            $this->installmentDueDates($sale)[0],
        );
    }

    public function testAnEnergyServiceSaleWithDownPaymentCreatesNoRates(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePersonId = $this->sell([
            'payment_type' => 'energy_service',
            'down_payment' => 250,
            'price_per_day' => 100,
            'minimum_payable_amount' => 100,
        ])->json('data.appliance_person.id');

        $this->assertSame(0, ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->count());
    }

    /**
     * @return list<string>
     */
    private function installmentDueDates(TestResponse $sale): array {
        $sale->assertStatus(200);

        return ApplianceRate::query()
            ->where('appliance_person_id', $sale->json('data.appliance_person.id'))
            ->whereDate('due_date', '>', Carbon::today())
            ->oldest('due_date')
            ->pluck('due_date')
            ->map(fn (string $dueDate): string => Carbon::parse($dueDate)->toDateString())
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function sell(array $plan): TestResponse {
        $person = PersonFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Solar Panel',
            'price' => 1000,
            'appliance_type_id' => ApplianceTypeFactory::new()->create()->id,
        ]);
        $seller = UserFactory::new()->create(['company_id' => $this->companyId]);

        return $this->actingAs($this->user)->post(
            "/api/appliances/person/{$appliance->id}/people/{$person->id}",
            array_merge([
                'user_id' => $seller->id,
                'rate_type' => 'monthly',
                'points' => '0,0',
            ], $plan)
        );
    }
}
