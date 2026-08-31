<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Transaction\Transaction;
use App\Plugins\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePaymentControllerTest extends TestCase {
    use CreateEnvironments;

    public function testReturnsActivePaymentPluginsForPaymentProvidersEndpoint(): void {
        $this->createTestData();

        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);

        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::WAVE_MONEY_PAYMENT_PROVIDER,
            'status' => Plugins::INACTIVE,
        ]);

        // Active, but inbound-only: cannot initiate payments, so it must not be offered.
        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::SWIFTA_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);

        $response = $this->actingAs($this->user)->get('/api/appliances/payment/providers');

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertEquals(MpmPlugin::PAYSTACK_PAYMENT_PROVIDER, $response['data'][0]['id']);
    }

    public function testReturnsEmptyListWhenNoActivePaymentPlugins(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->get('/api/appliances/payment/providers');

        $response->assertStatus(200);
        $this->assertCount(0, $response['data']);
    }

    public function testCreatesCashTransactionAndDispatchesProcessPaymentJob(): void {
        $this->createTestData();
        Queue::fake();

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
            'total_cost' => 500,
            'rate_count' => 5,
            'down_payment' => 0,
        ]);

        ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/payment/{$appliancePerson->id}",
            [
                'amount' => 100,
                'payment_provider' => 0,
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals($appliancePerson->id, $response['data']['appliance_person']['id']);
        $this->assertNotNull($response['data']['transaction_id']);

        Queue::assertPushed(ProcessPayment::class);

        // The queue is faked, so the transaction exists but has not been processed yet.
        $statusResponse = $this->actingAs($this->user)
            ->getJson("/api/appliances/payment/status/{$response['data']['transaction_id']}");

        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'data' => [
                'status' => 'processing',
                'processed' => false,
                'transaction_id' => $response['data']['transaction_id'],
            ],
        ]);
    }

    public function testWebInstallmentPaymentSetsNoAgentId(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePerson = $this->createSoldApplianceWithRate();

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/payment/{$appliancePerson->id}",
            ['amount' => 100, 'payment_provider' => 0],
        );

        $response->assertStatus(200);

        // Only the field app attributes a payment to an agent; an admin-panel payment has none,
        // which is what keeps TransactionSuccessfulListener from crediting anyone a commission.
        $this->assertNull(Transaction::query()->findOrFail($response['data']['transaction_id'])->agent_id);
    }

    public function testReturnsNotFoundForUnknownTransactionStatus(): void {
        $this->createTestData();

        $response = $this->actingAs($this->user)->getJson('/api/appliances/payment/status/999999');

        $response->assertStatus(404);
    }

    public function testReturnsRedirectUrlForPaystackPayment(): void {
        $this->createTestData();
        Queue::fake();

        Plugins::query()->create([
            'mpm_plugin_id' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            'status' => Plugins::ACTIVE,
        ]);

        $apiService = $this->createMock(PaystackApiService::class);
        $apiService->method('initializeTransaction')->willReturn([
            'error' => null,
            'redirectionUrl' => 'https://paystack.com/pay/test123',
            'reference' => 'ref_test123',
        ]);
        $this->app->instance(PaystackApiService::class, $apiService);

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
            'total_cost' => 500,
            'rate_count' => 5,
            'down_payment' => 0,
        ]);

        ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/payment/{$appliancePerson->id}",
            [
                'amount' => 100,
                'payment_provider' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            ]
        );

        $response->assertStatus(200);
        $this->assertEquals('https://paystack.com/pay/test123', $response['data']['redirect_url']);
        $this->assertEquals('ref_test123', $response['data']['reference']);

        Queue::assertNotPushed(ProcessPayment::class);
    }

    public function testRejectsUnknownPaymentProviderId(): void {
        $this->createTestData();

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
            'total_cost' => 500,
            'rate_count' => 5,
            'down_payment' => 0,
        ]);

        ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user)->postJson(
            "/api/appliances/payment/{$appliancePerson->id}",
            [
                'amount' => 100,
                'payment_provider' => 999,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('payment_provider');

        // Inbound-only providers exist in the PaymentProvider enum but cannot initiate payments.
        $response = $this->actingAs($this->user)->postJson(
            "/api/appliances/payment/{$appliancePerson->id}",
            [
                'amount' => 100,
                'payment_provider' => MpmPlugin::SWIFTA_PAYMENT_PROVIDER,
            ]
        );

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('payment_provider');
    }

    public function testAllowsPayingExactlyTheNextUnpaidInstallmentWhenRatesAreUneven(): void {
        $this->createTestData();
        Queue::fake();

        $appliancePerson = $this->seedUnevenRatePlan();

        $response = $this->actingAs($this->user)->postJson(
            "/api/appliances/payment/{$appliancePerson->id}",
            ['amount' => 385, 'payment_provider' => 0]
        );

        $response->assertStatus(200);
        Queue::assertPushed(ProcessPayment::class);
    }

    public function testRejectsPaymentBelowNextUnpaidInstallment(): void {
        $this->createTestData();
        Queue::fake();
        $this->withoutExceptionHandling();

        $appliancePerson = $this->seedUnevenRatePlan();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Payment amount can not smaller than installment cost');

        $this->actingAs($this->user)->postJson(
            "/api/appliances/payment/{$appliancePerson->id}",
            ['amount' => 384, 'payment_provider' => 0]
        );
    }

    /**
     * A plan whose first two (settled) rates are uneven catch-up rates and whose
     * next unpaid installment is 385 — the shape that exposed the fixed-index bug.
     */
    private function seedUnevenRatePlan(): AppliancePerson {
        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Appliance',
            'price' => 1690,
            'appliance_type_id' => $applianceType->id,
        ]);

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = AppliancePersonFactory::new()->create([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
            'total_cost' => 1690,
            'rate_count' => 5,
            'down_payment' => 40,
        ]);

        // [rate_cost, remaining]: two settled uneven rates, then even 385 installments.
        $plan = [[40, 0], [495, 0], [385, 385], [385, 385], [385, 385]];
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

    private function createSoldApplianceWithRate(): AppliancePerson {
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
            'total_cost' => 500,
            'rate_count' => 5,
            'down_payment' => 0,
        ]);

        ApplianceRate::query()->create([
            'appliance_person_id' => $appliancePerson->id,
            'rate_cost' => 100,
            'remaining' => 100,
            'remind' => 0,
            'due_date' => now()->addMonth(),
        ]);

        return $appliancePerson;
    }
}
