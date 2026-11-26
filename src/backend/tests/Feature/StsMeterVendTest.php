<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerMappingStatus;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\TokenProcessor;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Plugins;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\CalinMeter\Providers\CalinMeterServiceProvider;
use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Providers\StsMeterVendServiceProvider;
use Database\Factories\ApplianceFactory;
use Database\Factories\ApplianceTypeFactory;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\SolarHomeSystemFactory;
use Database\Factories\TariffFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\CreateEnvironments;
use Tests\TestCase;

class StsMeterVendTest extends TestCase {
    use CreateEnvironments;

    private const string DECODER_KEY = '0123456789ABCDEF';
    private const string TOP_UP_TOKEN = '58627975513348563046';
    private const string CLEAR_CREDIT_TOKEN = '26950712002385255186';

    protected function setUp(): void {
        parent::setUp();
        config()->set('services.sts.url', 'http://nxt-sts:8080');
        Plugins::query()->create(['mpm_plugin' => StsMeterVendServiceProvider::SLUG, 'status' => Plugins::ACTIVE]);
    }

    public function testTheKeyFileTemplateImportsAsIs(): void {
        $this->createTestData();
        $this->seedStsMeter('47000000001');

        $download = $this->actingAs($this->user)->get('/api/sts-meter-vend/keys/template');

        $download->assertStatus(200);
        $download->assertDownload('sts-meter-vend-keys-template.csv');
        $this->actingAs($this->user)
            ->postJson('/api/sts-meter-vend/keys/import', ['csv' => $this->csv($download->streamedContent())])
            ->assertStatus(200)
            ->assertJsonPath('data.imported', 1);
    }

    public function testItImportsKeysEncryptedAndReportsUnknownSerials(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0001');

        $response = $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv("serial,decoder_key\nSTS-0001,".self::DECODER_KEY."\nUNKNOWN,".self::DECODER_KEY."\nSTS-0001,nothex\n"),
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.imported', 1);
        $response->assertJsonCount(2, 'data.skipped');
        $response->assertJsonPath('data.skipped.0.serial', 'UNKNOWN');
        $response->assertJsonPath('data.unregistered', ['UNKNOWN']);
        $response->assertJsonPath('data.created', 0);

        $stored = StsMeter::query()->where('mpm_meter_id', $device->device->id)->firstOrFail();
        $storedCiphertext = (string) DB::connection('tenant')->table('sts_meters')->where('id', $stored->id)->value('decoder_key');
        $this->assertNotSame(self::DECODER_KEY, $storedCiphertext);
        $this->assertSame(self::DECODER_KEY, Crypt::decryptString($storedCiphertext));
        $this->assertSame(self::DECODER_KEY, $stored->decoder_key);
        $this->assertArrayNotHasKey('decoder_key', $stored->toArray());
        $this->assertSame(StsBaseDate::Year2014, $stored->base_date);
        $this->assertSame(StsEncryptionAlgorithm::Sta, $stored->encryption_algorithm);
        $this->assertSame(255, $stored->key_expiry_number);
    }

    public function testImportSkipsKeyProfilesTheEngineCannotVend(): void {
        $this->createTestData();
        $this->seedStsMeter('STS-0201');
        $this->seedStsMeter('STS-0202');
        $this->seedStsMeter('STS-0203');
        $this->seedStsMeter('STS-0204');
        $key = self::DECODER_KEY;

        $response = $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv(
                "serial,decoder_key,base_date,encryption_algorithm,key_expiry_number\n"
                ."STS-0201,{$key},2014,sta,255\n"
                ."STS-0202,{$key},1993,STA,255\n"
                ."STS-0203,{$key},2014,MISTY1,\n"
                ."STS-0204,{$key},2015,STA,300\n",
            ),
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.imported', 1);
        $response->assertJsonPath('data.skipped.0.serial', 'STS-0202');
        $response->assertJsonPath('data.skipped.0.reason', 'The token engine cannot vend base date 1993, algorithm STA, key expiry 255 yet.');
        $response->assertJsonPath('data.skipped.1.serial', 'STS-0203');
        $response->assertJsonPath('data.skipped.1.reason', 'The token engine cannot vend base date 2014, algorithm MISTY1, key expiry 255 yet.');
        $response->assertJsonPath('data.skipped.2.serial', 'STS-0204');
        $response->assertJsonPath('data.skipped.2.reason', 'Invalid base_date; expected 1993, 2014 or 2035.');
    }

    public function testImportCreatesUnregisteredMetersWithTheChosenManufacturerAndTariff(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter']);
        $tariff = TariffFactory::new()->create(['price' => 1440]);
        $existing = $this->seedStsMeter('STS-0401', vendingPlugin: null);

        $response = $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv("serial,decoder_key\nSTS-0401,".self::DECODER_KEY."\nSTS-0402,".self::DECODER_KEY."\n"),
            'manufacturer_id' => $manufacturer->id,
            'tariff_id' => $tariff->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.imported', 2);
        $response->assertJsonPath('data.created', 1);
        $response->assertJsonPath('data.unregistered', []);
        $this->assertSame(StsMeterVendServiceProvider::SLUG, $existing->refresh()->vending_plugin);

        $created = Device::query()->where('device_serial', 'STS-0402')->firstOrFail();
        $this->assertSame(Meter::RELATION_NAME, $created->device_type);
        $this->assertNull($created->person_id);
        $this->assertSame(StsMeterVendServiceProvider::SLUG, $created->vending_plugin);
        $this->assertInstanceOf(Meter::class, $created->device);
        $this->assertSame($manufacturer->id, $created->device->manufacturer_id);
        $this->assertSame($tariff->id, $created->device->tariff_id);
        $this->assertTrue(StsMeter::query()->where('mpm_meter_id', $created->device_id)->exists());
    }

    public function testImportCreatesNoMeterForARejectedKey(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter']);
        $tariff = TariffFactory::new()->create();

        $response = $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv("serial,decoder_key\nSTS-0403,nothex\n"),
            'manufacturer_id' => $manufacturer->id,
            'tariff_id' => $tariff->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.created', 0);
        $response->assertJsonPath('data.skipped.0.reason', 'decoder_key must be exactly 16 hexadecimal characters.');
        $this->assertFalse(Device::query()->where('device_serial', 'STS-0403')->exists());
        $this->assertFalse(Meter::query()->where('serial_number', 'STS-0403')->exists());
    }

    public function testCreatingMetersRequiresATariff(): void {
        $this->createTestData();
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter']);

        $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv("serial,decoder_key\nSTS-0404,".self::DECODER_KEY."\n"),
            'manufacturer_id' => $manufacturer->id,
        ])->assertStatus(422)->assertJsonValidationErrors('tariff_id');
    }

    public function testImportSkipsADeviceThatIsNotAMeter(): void {
        $this->createTestData();
        DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => SolarHomeSystemFactory::new()->create([
                'appliance_id' => ApplianceFactory::new()->create(['appliance_type_id' => ApplianceTypeFactory::new()->create()->id])->id,
            ])->id,
            'device_type' => SolarHomeSystem::RELATION_NAME,
            'device_serial' => 'SHS-0001',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/sts-meter-vend/keys/import', [
            'csv' => $this->csv("serial,decoder_key\nSHS-0001,".self::DECODER_KEY."\n"),
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('data.imported', 0);
        $response->assertJsonPath('data.skipped.0.reason', 'Not a meter.');
        $this->assertSame(0, StsMeter::query()->count());
    }

    public function testCapabilitiesOfferCreditAndClearCredit(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0003');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_types', ['add_credit', 'clear_credit']);
        $response->assertJsonPath('data.credit_unit', Token::UNIT_KWH);
    }

    public function testAdHocCreditVendsThroughTheEngineAndLinksTheIssue(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0004', pricePerKwh: 1440);
        $this->importKey('STS-0004');
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'add_credit',
            'amount' => 5000,
            'unit' => 'currency',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', self::TOP_UP_TOKEN);
        $response->assertJsonPath('data.token_type', Token::TYPE_ENERGY);
        $response->assertJsonPath('data.token_amount', 3.5);
        Http::assertSent(fn ($request): bool => $request['type'] === 'TOP_UP_KWH'
            && $request['kwh'] === 3.5
            && $request['decoderKey'] === self::DECODER_KEY);

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $transaction = Transaction::query()->findOrFail($token->transaction_id);
        $this->assertSame(Transaction::TYPE_AD_HOC, $transaction->type);

        $issue = StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame($token->id, $issue->token_id);
        $this->assertInstanceOf(StsTransaction::class, $transaction->originalTransaction?->manufacturerTransaction);
        $this->assertSame($issue->id, $transaction->originalTransaction->manufacturerTransaction->id);
    }

    public function testAdHocClearCreditVendsAClearCreditTokenAndLinksTheIssue(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0005');
        $this->importKey('STS-0005');
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::CLEAR_CREDIT_TOKEN])]);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", ['type' => 'clear_credit']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', self::CLEAR_CREDIT_TOKEN);
        $response->assertJsonPath('data.token_type', Token::TYPE_CLEAR_CREDIT);
        Http::assertSent(fn ($request): bool => $request['type'] === 'CLEAR_CREDIT');

        $token = Token::query()->where('device_id', $device->id)->firstOrFail();
        $issue = StsTransaction::query()->where('token', self::CLEAR_CREDIT_TOKEN)->firstOrFail();
        $this->assertSame($token->id, $issue->token_id);
        $this->assertSame($token->transaction_id, $issue->transaction_id);
    }

    public function testRecreatingAPaymentTokenResendsTheSameToken(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0007', pricePerKwh: 1440);
        $this->importKey('STS-0007');
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        Event::fake([PaymentSuccessEvent::class, TransactionSuccessfulEvent::class, TransactionFailedEvent::class]);
        $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'add_credit',
            'amount' => 5000,
            'unit' => 'currency',
        ])->assertStatus(201);
        $original = Token::query()->where('device_id', $device->id)->firstOrFail();
        $transaction = Transaction::query()->findOrFail($original->transaction_id);

        new TokenProcessor($this->companyId, TransactionDataContainer::initialize($transaction), reCreate: true)->handle();

        Http::assertSentCount(1);
        Event::assertNotDispatched(TransactionFailedEvent::class);
        $recreated = Token::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertNotSame($original->id, $recreated->id);
        $this->assertSame(self::TOP_UP_TOKEN, $recreated->token);
        $this->assertSame(3.5, (float) $recreated->token_amount);
        $this->assertSame($recreated->id, StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail()->token_id);
    }

    public function testDisablingThePluginStopsAKeyedMeterFromVending(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0008');
        $this->importKey('STS-0008');
        Http::fake();
        Plugins::query()->where('mpm_plugin', StsMeterVendServiceProvider::SLUG)->update(['status' => Plugins::INACTIVE]);

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.token_types', []);
        Http::assertNothingSent();
    }

    public function testAMeterWithoutAKeyCannotBeVended(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0006');
        Http::fake();

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'add_credit',
            'amount' => 1000,
            'unit' => 'currency',
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('No decoder key on file for meter STS-0006', $response->json('message'));
        Http::assertNothingSent();
        $this->assertSame(0, Token::query()->where('device_id', $device->id)->count());
    }

    public function testInstallLeavesNoRegistrationTailStep(): void {
        $this->createTestData();
        DB::connection('tenant')->table('registration_tail')->insert([
            'component' => StsMeterVendServiceProvider::SLUG,
            'adjusted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $manufacturerCount = Manufacturer::query()->count();

        Artisan::call('sts-meter-vend:install');

        $this->assertSame(0, DB::connection('tenant')->table('registration_tail')->where('component', StsMeterVendServiceProvider::SLUG)->count());
        $this->assertSame($manufacturerCount, Manufacturer::query()->count());
    }

    public function testImportingAKeyMakesStsTheMetersVendingProvider(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0301', vendingPlugin: null);
        $this->assertSame(CalinMeterServiceProvider::SLUG, $device->refresh()->vending_plugin);

        $this->actingAs($this->user)
            ->postJson('/api/sts-meter-vend/keys/import', ['csv' => $this->csv("serial,decoder_key\nSTS-0301,nothex\n")])
            ->assertJsonPath('data.skipped.0.reason', 'decoder_key must be exactly 16 hexadecimal characters.');
        $this->assertSame(CalinMeterServiceProvider::SLUG, $device->refresh()->vending_plugin);

        $this->importKey('STS-0301');

        $this->assertSame(StsMeterVendServiceProvider::SLUG, $device->refresh()->vending_plugin);
        $this->assertTrue(StsMeter::query()->where('mpm_meter_id', $device->device_id)->exists());
        $this->assertSame(ManufacturerMappingStatus::Mapped, $device->manufacturer_mapping_status);
    }

    public function testAnStsMeterIsMappedOnlyOnceItsKeyIsRecorded(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0401');

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info")
            ->assertStatus(200)
            ->assertJsonPath('data.supported', true)
            ->assertJsonPath('data.mapped', false)
            ->assertJsonPath('data.device', null);
        $this->assertSame(ManufacturerMappingStatus::NotMapped, $device->refresh()->manufacturer_mapping_status);

        $this->importKey('STS-0401');

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info")
            ->assertStatus(200)
            ->assertJsonPath('data.mapped', true)
            ->assertJsonPath('data.device.base_date', '2014')
            ->assertJsonPath('data.device.encryption_algorithm', 'STA')
            ->assertJsonPath('data.device.key_expiry_number', 255)
            ->assertJsonMissingPath('data.device.decoder_key');
    }

    public function testItListsManagedMetersWithKeyStatus(): void {
        $this->createTestData();
        $withKey = $this->seedStsMeter('STS-0101');
        $this->seedStsMeter('STS-0102');
        $this->seedStsMeter('CALIN-CLOUD-0103', vendingPlugin: null);
        $this->importKey('STS-0101');

        $response = $this->actingAs($this->user)->getJson('/api/sts-meter-vend/meters');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.serial_number', 'STS-0102');
        $response->assertJsonPath('data.0.manufacturer_mapping_status', 'unknown');
        $response->assertJsonPath('data.0.base_date', null);
        $response->assertJsonPath('data.1.serial_number', 'STS-0101');
        $response->assertJsonPath('data.1.manufacturer_mapping_status', 'mapped');
        $response->assertJsonPath('data.1.base_date', '2014');
        $response->assertJsonPath('data.1.encryption_algorithm', 'STA');
        $response->assertJsonPath('data.1.last_issued_at', null);
        $response->assertJsonPath('data.1.owner', trim($withKey->person->name.' '.$withKey->person->surname));
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('current_page', 1);
    }

    private function seedStsMeter(string $serial, float $pricePerKwh = 100, ?string $vendingPlugin = StsMeterVendServiceProvider::SLUG): Device {
        $manufacturer = Manufacturer::query()->firstWhere('api_name', 'CalinMeterApi')
            ?? ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => $pricePerKwh])->id,
            'serial_number' => $serial,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $serial,
            'vending_plugin' => $vendingPlugin,
        ]);
    }

    private function importKey(string $serial): void {
        $this->actingAs($this->user)
            ->postJson('/api/sts-meter-vend/keys/import', ['csv' => $this->csv("serial,decoder_key\n{$serial},".self::DECODER_KEY."\n")])
            ->assertStatus(200);
    }

    private function csv(string $content): UploadedFile {
        return UploadedFile::fake()->createWithContent('keys.csv', $content);
    }
}
