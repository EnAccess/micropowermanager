<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Events\AppliancePersonCreated;
use App\Events\NewLogEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\ProcessPayment;
use App\Jobs\TokenProcessor;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\ApplianceType;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Database\Factories\ApplianceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\CreateEnvironments;
use Tests\TestCase;

class DeviceSaleEligibilityTest extends TestCase {
    use CreateEnvironments;

    private Appliance $product;
    private Person $customer;
    private Manufacturer $openPaygoManufacturer;

    protected function setUp(): void {
        parent::setUp();
        $this->createTestData();
        Event::fake([AppliancePersonCreated::class, NewLogEvent::class, TransactionSuccessfulEvent::class]);
        $this->customer = PersonFactory::new()->isCustomer()->create();
        $applianceType = ApplianceType::query()->findOrFail(1);
        $applianceType->update(['paygo_enabled' => true]);
        $this->product = ApplianceFactory::new()->create([
            'appliance_type_id' => $applianceType->id,
            'price' => 1000,
        ]);
        $this->openPaygoManufacturer = ManufacturerFactory::new()->isShsManufacturer()->create([
            'name' => 'OpenPAYGO',
            'api_name' => 'OpenPaygoApi',
        ]);
    }

    public function testRegisteredCustomerDeviceCanBeSelectedAndSoldOnlyOnce(): void {
        $device = $this->registerDevice('MPMSELECT0001');
        $this->assertContains($device->id, $this->availableDeviceIds());

        $sale = $this->sell($device)->assertOk();
        $saleId = $sale->json('data.appliance_person.id');
        $this->assertSame($this->customer->id, $device->fresh()->person_id);
        $this->assertSame(5, ApplianceRate::query()->where('appliance_person_id', $saleId)->count());
        $this->assertNotContains($device->id, $this->availableDeviceIds());

        $this->sell($device)->assertUnprocessable()
            ->assertJsonValidationErrors(['device_serial']);
        $this->assertSame(1, AppliancePerson::query()->where('device_serial', $device->device_serial)->count());
        $this->assertSame(5, ApplianceRate::query()->where('appliance_person_id', $saleId)->count());
    }

    public function testAvailableFilterIncludesUnownedAndCustomerDevicesAndExcludesOtherOwnersAndSales(): void {
        $unowned = $this->createDevice('MPMSELECT0002');
        $owned = $this->createDevice('MPMSELECT0003', $this->customer);
        $otherOwned = $this->createDevice('MPMSELECT0004', PersonFactory::new()->isCustomer()->create());
        $sold = $this->createDevice('MPMSELECT0005', $this->customer);
        $this->sell($sold)->assertOk();

        $available = $this->availableDeviceIds();
        $this->assertContains($unowned->id, $available);
        $this->assertContains($owned->id, $available);
        $this->assertNotContains($otherOwned->id, $available);
        $this->assertNotContains($sold->id, $available);

        $unassigned = $this->actingAs($this->user)->getJson('/api/devices?unassigned=1')->assertOk();
        $serials = collect($unassigned->json('data'))->pluck('device_serial')->all();
        $this->assertContains($unowned->device_serial, $serials);
        $this->assertNotContains($owned->device_serial, $serials);
    }

    public function testSaleRejectsAnotherCustomerDeviceWithoutCreatingSaleOrRates(): void {
        $otherCustomer = PersonFactory::new()->isCustomer()->create();
        $device = $this->createDevice('MPMSELECT0006', $otherCustomer);
        $this->sell($device)->assertUnprocessable()
            ->assertJsonValidationErrors(['device_serial']);

        $this->assertSame($otherCustomer->id, $device->fresh()->person_id);
        $this->assertSame(0, AppliancePerson::query()->where('device_serial', $device->device_serial)->count());
        $this->assertSame(0, ApplianceRate::query()->count());
    }

    public function testDeletedSaleDoesNotBlockAReplacementSaleForTheSameCustomer(): void {
        $device = $this->createDevice('MPMSELECT0007', $this->customer);
        $saleId = $this->sell($device)->assertOk()->json('data.appliance_person.id');
        AppliancePerson::query()->findOrFail($saleId)->delete();

        $this->assertContains($device->id, $this->availableDeviceIds());
        $this->sell($device)->assertOk();
        $this->assertSame(1, AppliancePerson::query()->where('device_serial', $device->device_serial)->count());
        $this->assertSame(2, AppliancePerson::withTrashed()->where('device_serial', $device->device_serial)->count());
    }

    public function testSaleRejectsADeviceForADifferentApplianceProduct(): void {
        $device = $this->createDevice('MPMSELECT0009', $this->customer);
        $differentProduct = ApplianceFactory::new()->create(['appliance_type_id' => $this->product->appliance_type_id]);
        $device->device()->firstOrFail()->update(['appliance_id' => $differentProduct->id]);

        $this->assertNotContains($device->id, $this->availableDeviceIds());
        $this->sell($device)->assertUnprocessable()->assertJsonValidationErrors(['device_serial']);
        $this->assertSame(0, AppliancePerson::query()->where('device_serial', $device->device_serial)->count());
        $this->assertSame(0, ApplianceRate::query()->count());
    }

    public function testAvailableFilterRequiresAnExistingTenantCustomer(): void {
        $this->actingAs($this->user)->getJson('/api/devices?available_for_person_id=999999999')
            ->assertUnprocessable()->assertJsonValidationErrors(['available_for_person_id']);
        $deletedCustomer = PersonFactory::new()->isCustomer()->create();
        $deletedCustomer->delete();
        $this->actingAs($this->user)->getJson('/api/devices?available_for_person_id='.$deletedCustomer->id)
            ->assertUnprocessable()->assertJsonValidationErrors(['available_for_person_id']);
        $agent = PersonFactory::new()->isAgent()->create();
        $this->actingAs($this->user)->getJson('/api/devices?available_for_person_id='.$agent->id)
            ->assertUnprocessable()->assertJsonValidationErrors(['available_for_person_id']);
    }

    public function testRegisteredAndSoldDeviceUsesItsConfigurationForTheFirstPaymentToken(): void {
        Queue::fake([ProcessPayment::class, TokenProcessor::class]);
        config()->set('services.openpaygo_generator.url', 'https://generator.example');
        config()->set('services.openpaygo_generator.api_key', 'test-key');
        Http::fake(['https://generator.example/generate' => Http::response(['token' => '123456789', 'nextCounter' => 2])]);
        $device = $this->registerDevice('MPMSELECT0008');
        $saleId = $this->sell($device, 'weekly')->assertOk()->json('data.appliance_person.id');
        $payment = $this->actingAs($this->user)->postJson('/api/appliances/payment/'.$saleId, [
            'amount' => 200,
            'payment_provider' => 0,
        ])->assertOk();
        $transactionId = $payment->json('data.transaction_id');
        new ApplianceTransactionProcessor($this->companyId, $transactionId)->executeJob();
        Queue::assertPushed(TokenProcessor::class);
        $tokenJob = Queue::pushed(TokenProcessor::class)->first();
        $tokenJob->executeJob();

        $token = Transaction::query()->findOrFail($transactionId)->token()->firstOrFail();
        $this->assertSame('123456789', $token->token);
        $this->assertSame(Token::TYPE_TIME, $token->token_type);
        $this->assertSame(7, (int) $token->token_amount);
        $this->assertSame(2, $device->openPaygoConfiguration()->firstOrFail()->next_counter);
        Http::assertSent(fn ($request): bool => $request['counter'] === 0 && $request['value'] === 28);
        Event::assertDispatchedTimes(TransactionSuccessfulEvent::class, 1);
        $this->getJson('/api/appliances/payment/status/'.$transactionId)->assertOk()
            ->assertJsonPath('data.processed', true)
            ->assertJsonPath('data.token_issuance.status', 'completed');
        $this->getJson('/api/solar-home-systems/'.$device->device_id.'/transactions')->assertOk()
            ->assertJsonPath('data.0.transaction.token.token', '123456789')
            ->assertJsonPath('data.0.transaction.token_issuance.status', 'completed');
    }

    private function registerDevice(string $serialNumber): Device {
        $this->actingAs($this->user)->postJson('/api/devices/openpaygo', [
            'serial_number' => $serialNumber,
            'manufacturer_id' => $this->openPaygoManufacturer->id,
            'appliance_id' => $this->product->id,
            'person_id' => $this->customer->id,
            'secretKeyHex' => '0123456789abcdef0123456789abcdef',
            'startingCode' => 321,
            'nextCounter' => 0,
            'timeDivider' => 4,
        ])->assertCreated();

        return Device::query()->where('device_serial', $serialNumber)->firstOrFail();
    }

    private function createDevice(string $serialNumber, ?Person $owner = null): Device {
        $solarHomeSystem = SolarHomeSystem::query()->create([
            'serial_number' => $serialNumber,
            'manufacturer_id' => $this->openPaygoManufacturer->id,
            'appliance_id' => $this->product->id,
        ]);

        return Device::query()->create([
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_id' => $solarHomeSystem->id,
            'device_serial' => $serialNumber,
            'person_id' => $owner?->id,
        ]);
    }

    private function availableDeviceIds(): array {
        $response = $this->actingAs($this->user)->getJson(sprintf(
            '/api/devices?available_for_person_id=%d&appliance_id=%d',
            $this->customer->id,
            $this->product->id,
        ))->assertOk();

        return collect($response->json('data'))->pluck('id')->all();
    }

    private function sell(Device $device, string $installmentType = 'monthly'): TestResponse {
        return $this->actingAs($this->user)->postJson(sprintf(
            '/api/appliances/person/%d/people/%d',
            $this->product->id,
            $this->customer->id,
        ), [
            'user_id' => $this->user->id,
            'cost' => 1000,
            'rate' => 5,
            'rate_type' => $installmentType,
            'device_serial' => $device->device_serial,
        ]);
    }
}
