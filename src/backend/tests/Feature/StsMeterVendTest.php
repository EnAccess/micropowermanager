<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Events\PaymentSuccessEvent;
use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Jobs\TokenProcessor;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Plugins;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use App\Plugins\StsMeterVend\Services\ManufacturerService;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
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
        Plugins::query()->create(['mpm_plugin_id' => MpmPlugin::STS_METER_VEND, 'status' => Plugins::ACTIVE]);
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

    public function testMappingReflectsWhetherAKeyIsOnFile(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0002');

        $before = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info");
        $before->assertStatus(200);
        $before->assertJsonPath('data.supported', true);
        $before->assertJsonPath('data.mapped', false);

        $this->importKey('STS-0002');

        $after = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/device-info");
        $after->assertJsonPath('data.mapped', true);
    }

    public function testCapabilitiesOfferCreditAndResetButNotUnlock(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0003');

        $response = $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities");

        $response->assertStatus(200);
        $response->assertJsonPath('data.token_generation', true);
        $response->assertJsonPath('data.credit_unit', Token::UNIT_KWH);
        $response->assertJsonPath('data.reset_token', true);
        $response->assertJsonPath('data.unlock_token', false);
    }

    public function testAdHocCreditVendsThroughTheEngineAndLinksTheIssue(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0004', pricePerKwh: 1440);
        $this->importKey('STS-0004');
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'credit',
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

    public function testAdHocResetVendsAClearCreditTokenAndLinksTheIssue(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0005');
        $this->importKey('STS-0005');
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::CLEAR_CREDIT_TOKEN])]);

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", ['type' => 'reset']);

        $response->assertStatus(201);
        $response->assertJsonPath('data.token', self::CLEAR_CREDIT_TOKEN);
        $response->assertJsonPath('data.token_type', Token::TYPE_RESET);
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
            'type' => 'credit',
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
        Plugins::query()->where('mpm_plugin_id', MpmPlugin::STS_METER_VEND)->update(['status' => Plugins::INACTIVE]);

        $this->actingAs($this->user)->getJson("/api/devices/{$device->id}/capabilities")
            ->assertStatus(200)
            ->assertJsonPath('data.token_generation', false);
        Http::assertNothingSent();
    }

    public function testAMeterWithoutAKeyCannotBeVended(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0006');
        Http::fake();

        $response = $this->actingAs($this->user)->postJson("/api/devices/{$device->id}/token", [
            'type' => 'credit',
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
            'component' => 'StsMeterVend',
            'adjusted' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Artisan::call('sts-meter-vend:install');

        $this->assertSame(0, DB::connection('tenant')->table('registration_tail')->where('component', 'StsMeterVend')->count());
        $this->assertSame(
            ['CalinMeterApi', 'CalinSmartMeterApi'],
            Manufacturer::query()->whereIn('api_name', array_keys(ManufacturerService::SUPPORTED_MANUFACTURERS))->orderBy('api_name')->pluck('api_name')->all(),
        );
    }

    public function testACalinMeterIsOfferedNativeStsAndItsKeyImportWaitsForIt(): void {
        $this->createTestData();
        $device = $this->seedStsMeter('STS-0301', vendingProvider: null);

        $this->actingAs($this->user)->getJson("/api/meters/{$device->device_id}/vending-providers")
            ->assertStatus(200)
            ->assertExactJson(['data' => [['key' => StsMeterApi::VENDING_PROVIDER, 'name' => 'Native STS (nxt-sts)']]]);

        $this->actingAs($this->user)
            ->postJson('/api/sts-meter-vend/keys/import', ['csv' => $this->csv("serial,decoder_key\nSTS-0301,".self::DECODER_KEY."\n")])
            ->assertJsonPath('data.skipped.0.reason', 'Meter is not vended through Native STS; set its vending provider first.');

        $this->actingAs($this->user)
            ->putJson("/api/meters/{$device->device_id}", ['id' => $device->device_id, 'vending_provider' => StsMeterApi::VENDING_PROVIDER])
            ->assertStatus(200);
        $this->importKey('STS-0301');
        $this->assertTrue(StsMeter::query()->where('mpm_meter_id', $device->device_id)->exists());
    }

    public function testItListsManagedMetersWithKeyStatus(): void {
        $this->createTestData();
        $withKey = $this->seedStsMeter('STS-0101');
        $this->seedStsMeter('STS-0102');
        $this->seedStsMeter('CALIN-CLOUD-0103', vendingProvider: null);
        $this->importKey('STS-0101');

        $response = $this->actingAs($this->user)->getJson('/api/sts-meter-vend/meters');

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('data.0.serial_number', 'STS-0102');
        $response->assertJsonPath('data.0.key_present', false);
        $response->assertJsonPath('data.1.serial_number', 'STS-0101');
        $response->assertJsonPath('data.1.key_present', true);
        $response->assertJsonPath('data.1.base_date', '2014');
        $response->assertJsonPath('data.1.encryption_algorithm', 'STA');
        $response->assertJsonPath('data.1.last_issued_at', null);
        $response->assertJsonPath('data.1.owner', trim($withKey->person->name.' '.$withKey->person->surname));
        $response->assertJsonPath('total', 2);
        $response->assertJsonPath('current_page', 1);
    }

    private function seedStsMeter(string $serial, float $pricePerKwh = 100, ?string $vendingProvider = StsMeterApi::VENDING_PROVIDER): Device {
        $manufacturer = Manufacturer::query()->firstWhere('api_name', 'CalinMeterApi')
            ?? ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => $pricePerKwh])->id,
            'serial_number' => $serial,
            'vending_provider' => $vendingProvider,
        ]);

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $serial,
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
