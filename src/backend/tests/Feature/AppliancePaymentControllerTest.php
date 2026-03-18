<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\MpmPlugin;
use App\Models\Plugins;
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
    }

    public function testReturnsRedirectUrlForPaystackPayment(): void {
        $this->createTestData();
        Queue::fake();

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

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/payment/{$appliancePerson->id}",
            [
                'amount' => 100,
                'payment_provider' => 999,
            ]
        );

        $response->assertStatus(500);
    }
}
