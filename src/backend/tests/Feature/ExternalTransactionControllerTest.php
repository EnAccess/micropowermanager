<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\ApplianceRate;
use App\Models\Company;
use App\Models\Person\Person;
use App\Models\Transaction\ThirdPartyTransaction;
use Database\Factories\Address\AddressFactory;
use Database\Factories\AppliancePersonFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\Person\PersonFactory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExternalTransactionControllerTest extends TestCase {
    use WithFaker;

    private const string PAY_URL = '/api/appliances/payment/third-party';
    private const string DEVICES_URL = '/api/appliances/payment/third-party/devices';

    private function createApiKey(string $name = 'Vodacom'): string {
        $plaintext = Str::random(40);

        Company::query()->findOrFail($this->companyId)->apiKeys()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $plaintext),
            'active' => true,
        ]);

        return $plaintext;
    }

    private function createPersonWithPhone(string $phone): Person {
        $person = PersonFactory::new()->create();

        $address = AddressFactory::new()->make(['phone' => $phone]);
        $address->owner()->associate($person);
        $address->save();

        return $person;
    }

    private function createAppliancePersonWithSerial(string $serial, ?Person $person = null): AppliancePerson {
        $person ??= PersonFactory::new()->create();
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
            'device_serial' => $serial,
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

    public function testRegistersTransactionAndDispatchesProcessPaymentJob(): void {
        Queue::fake();
        $token = $this->createApiKey('Vodacom');
        $this->createAppliancePersonWithSerial('SERIAL-001');

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::PAY_URL, [
            'serial' => 'SERIAL-001',
            'amount' => 100,
            'external_reference' => 'vodacom-ref-1',
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response['data']['transaction_id']);
        $this->assertEquals('SERIAL-001', $response['data']['serial']);
        $this->assertEquals(100, $response['data']['amount']);
        $this->assertEquals(0, $response['data']['remaining_amount']);
        $this->assertNull($response['data']['minimum_payable_amount']);

        Queue::assertPushed(ProcessPayment::class);

        $thirdPartyTransaction = ThirdPartyTransaction::query()->where('transaction_id', 'vodacom-ref-1')->first();
        $this->assertNotNull($thirdPartyTransaction);
        $this->assertEquals('Vodacom', $thirdPartyTransaction->description);
    }

    public function testReturnsNotFoundForUnknownSerial(): void {
        $token = $this->createApiKey();

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::PAY_URL, [
            'serial' => 'NO-SUCH-SERIAL',
            'amount' => 100,
            'external_reference' => 'vodacom-ref-2',
        ]);

        $response->assertStatus(404);
    }

    public function testRejectsInvalidAmount(): void {
        $token = $this->createApiKey();
        $this->createAppliancePersonWithSerial('SERIAL-003');

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::PAY_URL, [
            'serial' => 'SERIAL-003',
            'amount' => 999,
            'external_reference' => 'vodacom-ref-3',
        ]);

        $response->assertStatus(422);
    }

    public function testDeduplicatesRepeatedExternalReference(): void {
        Queue::fake();
        $token = $this->createApiKey();
        $this->createAppliancePersonWithSerial('SERIAL-004');

        $first = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::PAY_URL, [
            'serial' => 'SERIAL-004',
            'amount' => 100,
            'external_reference' => 'vodacom-ref-4',
        ]);
        $second = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::PAY_URL, [
            'serial' => 'SERIAL-004',
            'amount' => 100,
            'external_reference' => 'vodacom-ref-4',
        ]);

        $first->assertStatus(200);
        $second->assertStatus(200);
        $this->assertEquals($first['data']['transaction_id'], $second['data']['transaction_id']);
        $this->assertEquals(1, ThirdPartyTransaction::query()->where('transaction_id', 'vodacom-ref-4')->count());
    }

    public function testRejectsMissingApiKey(): void {
        $this->createAppliancePersonWithSerial('SERIAL-005');

        $response = $this->postJson(self::PAY_URL, [
            'serial' => 'SERIAL-005',
            'amount' => 100,
            'external_reference' => 'vodacom-ref-5',
        ]);

        // Same behavior as every other api-key-guarded third-party endpoint (Vodacom, Odyssey,
        // Ecreee): the resolver can't identify a company from a missing/invalid key and rejects
        // the request in UserDefaultDatabaseConnectionMiddleware, before routing/route middleware.
        $response->assertStatus(422);
    }

    public function testListsPayableInstallmentDeviceForPhone(): void {
        $token = $this->createApiKey();
        $phone = $this->faker->unique()->e164PhoneNumber();
        $person = $this->createPersonWithPhone($phone);
        $this->createAppliancePersonWithSerial('SERIAL-010', $person);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::DEVICES_URL, [
            'phone' => $phone,
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('SERIAL-010', $response['data'][0]['serial']);
        $this->assertEquals('installment', $response['data'][0]['payment_type']);
        $this->assertEquals(100, $response['data'][0]['remaining_amount']);
        $this->assertNull($response['data'][0]['minimum_payable_amount']);
    }

    public function testListsPayableEnergyServiceDeviceForPhone(): void {
        $token = $this->createApiKey();
        $phone = $this->faker->unique()->e164PhoneNumber();
        $person = $this->createPersonWithPhone($phone);
        $appliancePerson = $this->createAppliancePersonWithSerial('SERIAL-011', $person);
        $appliancePerson->update(['payment_type' => AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, 'minimum_payable_amount' => 50]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::DEVICES_URL, [
            'phone' => $phone,
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, $response['data']);
        $this->assertEquals(AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE, $response['data'][0]['payment_type']);
        $this->assertNull($response['data'][0]['remaining_amount']);
        $this->assertEquals(50, $response['data'][0]['minimum_payable_amount']);
    }

    public function testReturnsUnionOfDevicesWhenPhoneMatchesMultipleCustomers(): void {
        $token = $this->createApiKey();
        $sharedPhone = $this->faker->unique()->e164PhoneNumber();
        $personA = $this->createPersonWithPhone($sharedPhone);
        $personB = $this->createPersonWithPhone($sharedPhone);
        $this->createAppliancePersonWithSerial('SERIAL-012', $personA);
        $this->createAppliancePersonWithSerial('SERIAL-013', $personB);

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::DEVICES_URL, [
            'phone' => $sharedPhone,
        ]);

        $response->assertStatus(200);
        $serials = array_column($response['data'], 'serial');
        $this->assertContains('SERIAL-012', $serials);
        $this->assertContains('SERIAL-013', $serials);
    }

    public function testExcludesSoftDeletedPlanFromDeviceList(): void {
        $token = $this->createApiKey();
        $phone = $this->faker->unique()->e164PhoneNumber();
        $person = $this->createPersonWithPhone($phone);
        $appliancePerson = $this->createAppliancePersonWithSerial('SERIAL-014', $person);
        $appliancePerson->delete();

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::DEVICES_URL, [
            'phone' => $phone,
        ]);

        $response->assertStatus(200);
        $this->assertCount(0, $response['data']);
    }

    public function testReturnsEmptyListForUnknownPhone(): void {
        $token = $this->createApiKey();

        $response = $this->withHeader('Authorization', "Bearer {$token}")->postJson(self::DEVICES_URL, [
            'phone' => $this->faker->unique()->e164PhoneNumber(),
        ]);

        $response->assertStatus(200);
        $this->assertCount(0, $response['data']);
    }

    public function testDevicesEndpointRejectsMissingApiKey(): void {
        $response = $this->postJson(self::DEVICES_URL, ['phone' => $this->faker->unique()->e164PhoneNumber()]);

        $response->assertStatus(422);
    }
}
