<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\ApplianceTokenNotProcessedException;
use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\TokenProcessor;
use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\PaymentHistory;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Services\CashTransactionService;
use Carbon\Carbon;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DownPaymentProcessingTest extends TestCase {
    use CreateEnvironments;

    public function testTheDownPaymentSettlesItsRateAndBuysDaysAtThePlansDailyPrice(): void {
        $this->createTestData();
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class]);

        $device = $this->seedPaygoShs();
        $appliancePersonId = $this->sell($device, ['cost' => 1000, 'rate' => 5, 'rate_type' => 'monthly', 'down_payment' => 200]);

        $transaction = $this->processDownPayment($device, 200);

        $rates = ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->oldest('due_date')->get();
        $this->assertSame(0, $rates->first()->remaining);
        $this->assertTrue($rates->skip(1)->every(fn (ApplianceRate $rate): bool => $rate->remaining === $rate->rate_cost));
        $this->assertSame(Transaction::TYPE_DOWN_PAYMENT, $transaction->refresh()->type);

        $history = PaymentHistory::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame(Transaction::TYPE_DOWN_PAYMENT, $history->payment_type);
        $this->assertSame('cash_transaction', $history->payment_service);

        // 200 at 160 per period of one month (28 to 31 days) buys 35 to 39 days.
        $token = $this->vendToken();
        $this->assertSame(Token::TYPE_TIME, $token->token_type);
        $this->assertSame(Token::UNIT_DAYS, $token->token_unit);
        $this->assertSame(ceil(200 * $this->installmentPeriodInDays($rates) / 160), (float) $token->token_amount);
    }

    public function testASmallDownPaymentOnAWeeklyPlanBuysAFractionOfAWeek(): void {
        $this->createTestData();
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class]);

        $device = $this->seedPaygoShs();
        $this->sell($device, ['cost' => 90000, 'rate' => 12, 'rate_type' => 'weekly', 'down_payment' => 90]);

        $this->processDownPayment($device, 90);
        $token = $this->vendToken();

        // 90 at 7492 per week is well under a day; a day is the smallest credit vended.
        $this->assertSame(1.0, (float) $token->token_amount);
    }

    public function testADownPaymentOfTheFullPriceUnlocksTheDevice(): void {
        $this->createTestData();
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class]);

        $device = $this->seedPaygoShs();
        $appliancePersonId = $this->sell($device, ['cost' => 1000, 'rate' => 0, 'rate_type' => 'monthly', 'down_payment' => 1000]);

        $this->processDownPayment($device, 1000);
        $token = $this->vendToken();

        $this->assertSame(0, ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->sum('remaining'));
        $this->assertSame(Token::TYPE_UNLOCK, $token->token_type);
    }

    public function testAPaymentBelowTheDownPaymentIsRejected(): void {
        $this->createTestData();
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class, TransactionFailedEvent::class]);

        $device = $this->seedPaygoShs();
        $appliancePersonId = $this->sell($device, ['cost' => 1000, 'rate' => 5, 'rate_type' => 'monthly', 'down_payment' => 200]);

        try {
            $this->processDownPayment($device, 150);
            $this->fail('A payment below the down payment was accepted.');
        } catch (ApplianceTokenNotProcessedException) {
        }

        $this->assertSame(200, ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->oldest('due_date')->firstOrFail()->remaining);
        Queue::assertNotPushed(TokenProcessor::class);
    }

    public function testAnEnergyServiceDownPaymentBuysDaysAtThePricePerDay(): void {
        $this->createTestData();
        Queue::fake();
        Event::fake([TransactionSuccessfulEvent::class]);

        $device = $this->seedPaygoShs();
        $appliancePersonId = $this->sell($device, [
            'payment_type' => 'energy_service',
            'down_payment' => 250,
            'price_per_day' => 100,
            'minimum_payable_amount' => 100,
        ]);

        $transaction = $this->processDownPayment($device, 250);
        $token = $this->vendToken();

        $paidRate = ApplianceRate::query()->where('appliance_person_id', $appliancePersonId)->firstOrFail();
        $this->assertSame(250, $paidRate->rate_cost);
        $this->assertSame(0, $paidRate->remaining);
        $this->assertSame(
            Transaction::TYPE_DOWN_PAYMENT,
            PaymentHistory::query()->where('transaction_id', $transaction->id)->firstOrFail()->payment_type,
        );
        $this->assertSame(3.0, (float) $token->token_amount);
    }

    private function seedPaygoShs(): Device {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'shs', 'api_name' => 'DemoShsManufacturerApi']);
        $appliance = ApplianceFactory::new()->create([
            'price' => 1000,
            'appliance_type_id' => ApplianceTypeFactory::new()->create(['paygo_enabled' => true])->id,
        ]);
        $solarHomeSystem = SolarHomeSystemFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'appliance_id' => $appliance->id,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => null,
            'device_id' => $solarHomeSystem->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
        ]);
    }

    /**
     * @param array<string, mixed> $plan
     */
    private function sell(Device $device, array $plan): int {
        $person = PersonFactory::new()->create();
        $applianceId = $device->device->appliance_id;

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/person/{$applianceId}/people/{$person->id}",
            array_merge([
                'user_id' => $this->user->id,
                'device_serial' => $device->device_serial,
                'points' => '0,0',
            ], $plan)
        );
        $response->assertStatus(200);

        return $response->json('data.appliance_person.id');
    }

    private function processDownPayment(Device $device, float $amount): Transaction {
        $transaction = resolve(CashTransactionService::class)->createTransaction(
            $this->user->id,
            $amount,
            '-',
            $device->device_serial,
            Transaction::TYPE_DEFERRED_PAYMENT,
        );

        new ApplianceTransactionProcessor($this->companyId, $transaction->id)->handle();

        return $transaction;
    }

    private function vendToken(): Token {
        $tokenProcessor = Queue::pushed(TokenProcessor::class)->first();
        $this->assertInstanceOf(TokenProcessor::class, $tokenProcessor);

        $tokenProcessor->handle();

        return Token::query()->firstOrFail();
    }

    /**
     * @param Collection<int, ApplianceRate> $ratesInScheduleOrder
     */
    private function installmentPeriodInDays(Collection $ratesInScheduleOrder): float {
        return floor(Carbon::parse($ratesInScheduleOrder[1]->due_date)->diffInDays(Carbon::parse($ratesInScheduleOrder[2]->due_date)));
    }
}
