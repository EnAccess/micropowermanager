<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\MpmPlugin;
use App\Plugins\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\UserFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\CreateEnvironments;
use Tests\TestCase;

class AppliancePersonControllerDownPaymentTest extends TestCase {
    use CreateEnvironments;

    /**
     * @return array<string, mixed>
     */
    private function buildSaleRequest(int $applianceId, int $personId, int $userId, float $downPayment): array {
        return [
            'id' => $applianceId,
            'person_id' => $personId,
            'user_id' => $userId,
            'cost' => 1000,
            'rate' => 5,
            'rate_type' => 'monthly',
            'down_payment' => $downPayment,
            'points' => '0,0',
        ];
    }

    public function testItFiresPaymentSuccessEventForCashDownPayment(): void {
        $this->createTestData();
        Event::fake([PaymentSuccessEvent::class, TransactionSuccessfulEvent::class]);

        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Solar Panel',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);
        $seller = UserFactory::new()->create(['company_id' => $this->companyId]);

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/person/{$appliance->id}/people/{$person->id}",
            array_merge($this->buildSaleRequest($appliance->id, $person->id, $seller->id, 200), [
                'payment_provider' => 0,
            ])
        );

        $response->assertStatus(200);

        Event::assertDispatched(PaymentSuccessEvent::class);
        Event::assertDispatched(TransactionSuccessfulEvent::class);

        $this->assertNotNull($response['data']['appliance_person']);
    }

    public function testItDispatchesProcessPaymentJobForPaystackDownPayment(): void {
        $this->createTestData();
        Queue::fake();

        $apiService = $this->createMock(PaystackApiService::class);
        $apiService->method('initializeTransaction')->willReturn([
            'error' => null,
            'redirectionUrl' => 'https://paystack.com/pay/dp_test',
            'reference' => 'ref_dp_test',
        ]);
        $this->app->instance(PaystackApiService::class, $apiService);

        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Solar Panel',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);
        $seller = UserFactory::new()->create(['company_id' => $this->companyId]);

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/person/{$appliance->id}/people/{$person->id}",
            array_merge($this->buildSaleRequest($appliance->id, $person->id, $seller->id, 200), [
                'payment_provider' => MpmPlugin::PAYSTACK_PAYMENT_PROVIDER,
            ])
        );

        $response->assertStatus(200);

        Queue::assertPushed(ProcessPayment::class);

        $this->assertNotNull($response['data']['appliance_person']);
        $this->assertEquals('https://paystack.com/pay/dp_test', $response['data']['redirect_url']);
    }

    public function testItCreatesAppliancePersonWithoutDownPayment(): void {
        $this->createTestData();
        Event::fake();

        $person = PersonFactory::new()->create();
        $applianceType = ApplianceTypeFactory::new()->create();
        $appliance = Appliance::query()->create([
            'name' => 'Test Solar Panel',
            'price' => 1000,
            'appliance_type_id' => $applianceType->id,
        ]);
        $seller = UserFactory::new()->create(['company_id' => $this->companyId]);

        $response = $this->actingAs($this->user)->post(
            "/api/appliances/person/{$appliance->id}/people/{$person->id}",
            $this->buildSaleRequest($appliance->id, $person->id, $seller->id, 0)
        );

        $response->assertStatus(200);

        Event::assertNotDispatched(PaymentSuccessEvent::class);
        $this->assertNotNull($response['data']['appliance_person']);
    }
}
