<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTO\TransactionDataContainer;
use App\Enums\ManufacturerCapability;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\StsMeterVend\Exceptions\StsApiResponseException;
use App\Plugins\StsMeterVend\Exceptions\StsVendRefusedException;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use App\Plugins\StsMeterVend\Modules\Api\StsMeterApi;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use Database\Factories\TransactionFactory;
use Database\Factories\UserFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StsMeterApiTest extends TestCase {
    private const string DECODER_KEY = '0123456789ABCDEF';
    private const string TOP_UP_TOKEN = '58627975513348563046';
    private const string CLEAR_CREDIT_TOKEN = '26950712002385255186';

    protected function setUp(): void {
        parent::setUp();
        config()->set('services.sts.url', 'http://nxt-sts:8080');
        $this->travelTo(Carbon::parse('2026-10-08 10:15:30'));
    }

    public function testItDeclaresCreditAndResetOnly(): void {
        $this->assertSame(
            [ManufacturerCapability::CreditToken, ManufacturerCapability::ResetToken],
            $this->api()->capabilities(),
        );
    }

    public function testChargeDeviceRoundsUpToTenthsAndRecordsTheIssue(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0001', pricePerKwh: 1440);
        $transaction = $this->transaction($device, amount: 5000);

        $result = $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));

        Http::assertSent(fn (Request $request): bool => $request->url() === 'http://nxt-sts:8080/token'
            && $request['type'] === 'TOP_UP_KWH'
            && $request['kwh'] === 3.5
            && $request['issueDate'] === '2026-10-08T10:15:00'
            && $request['decoderKey'] === self::DECODER_KEY);
        $this->assertSame(self::TOP_UP_TOKEN, $result['token']);
        $this->assertSame(Token::TYPE_ENERGY, $result['token_type']);
        $this->assertSame(Token::UNIT_KWH, $result['token_unit']);
        $this->assertSame(3.5, $result['token_amount']);

        $issue = StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertSame('TOP_UP_KWH', $issue->token_type);
        $this->assertSame(self::TOP_UP_TOKEN, $issue->token);
        $this->assertSame(3.5, $issue->kwh);
        $this->assertSame('2026-10-08 10:15:00', $issue->issued_at->toDateTimeString());
        $this->assertSame($issue->id, $transaction->originalTransaction?->manufacturerTransaction?->id);
    }

    public function testTheTokenRequestIsBuiltFromTheKeyRecordAndTheIssue(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0019', pricePerKwh: 1440);
        $transaction = $this->transaction($device, amount: 5000);

        $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));

        $issue = StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail();
        Http::assertSent(fn (Request $request): bool => $request->data() === [
            'type' => 'TOP_UP_KWH',
            'issueDate' => '2026-10-08T10:15:00',
            'randomNumber' => $issue->random_number,
            'decoderKey' => self::DECODER_KEY,
            'kwh' => 3.5,
        ]);
    }

    public function testTwoTokensInTheSameMinuteAreIssuedAMinuteApart(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0002');

        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));
        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));

        $issueDates = Http::recorded()->map(fn (array $exchange): string => $exchange[0]['issueDate'])->all();
        $this->assertSame(['2026-10-08T10:15:00', '2026-10-08T10:16:00'], $issueDates);
        $this->assertSame('2026-10-08 10:16:00', $this->stsMeter($device)->last_issued_at?->toDateTimeString());
    }

    public function testIssueFollowsALastIssuedMinuteAheadOfTheClock(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0003');
        $this->stsMeter($device)->update(['last_issued_at' => Carbon::parse('2026-10-08 10:25:00')]);

        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));

        Http::assertSent(fn (Request $request): bool => $request['issueDate'] === '2026-10-08T10:26:00');
    }

    public function testIssueIsRefusedWhenTheMeterRunsTooFarAheadOfTheClock(): void {
        Http::fake();
        $device = $this->stsDevice('STS-0004');
        $this->stsMeter($device)->update(['last_issued_at' => Carbon::parse('2026-10-08 11:15:00')]);

        try {
            $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));
            $this->fail('Expected StsVendRefusedException');
        } catch (StsVendRefusedException $e) {
            $this->assertStringContainsString('Too many tokens were issued for meter STS-0004', $e->getMessage());
        }
        Http::assertNothingSent();
    }

    public function testVendingTheSameTransactionAgainReturnsTheSameToken(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0005', pricePerKwh: 1440);
        $transaction = $this->transaction($device, amount: 5000);

        $first = $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));
        $device->device->tariff->update(['price' => 100]);
        $this->travel(5)->minutes();
        $second = $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));

        Http::assertSentCount(1);
        $this->assertSame($first, $second);
        $this->assertSame(1, StsTransaction::query()->where('transaction_id', $transaction->id)->count());
        $this->assertSame('2026-10-08 10:15:00', $this->stsMeter($device)->last_issued_at?->toDateTimeString());
    }

    public function testAFailedEngineCallKeepsTheReservationForTheNextAttempt(): void {
        Http::fakeSequence('nxt-sts:8080/token')
            ->push(['error' => 'boom'], 500)
            ->push(['token' => self::TOP_UP_TOKEN]);
        $device = $this->stsDevice('STS-0006');
        $transaction = $this->transaction($device, amount: 500);

        try {
            $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));
            $this->fail('Expected StsApiResponseException');
        } catch (StsApiResponseException $e) {
            $this->assertSame(502, $e->render(request())->getStatusCode());
        }

        $reserved = StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail();
        $this->assertNull($reserved->token);
        $this->assertSame('2026-10-08 10:15:00', $this->stsMeter($device)->last_issued_at?->toDateTimeString());

        $this->travel(5)->minutes();
        $result = $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));

        [$failedRequest, $retriedRequest] = Http::recorded()->map(fn (array $exchange): array => $exchange[0]->data())->all();
        $this->assertSame($failedRequest, $retriedRequest);
        $this->assertSame(self::TOP_UP_TOKEN, $result['token']);
        $this->assertSame(self::TOP_UP_TOKEN, $reserved->refresh()->token);
    }

    public function testAnEngineClientErrorIsReportedAsBadGatewayWithItsField(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['error' => 'kwh must not exceed 1820162.4', 'field' => 'kwh'], 400)]);
        $device = $this->stsDevice('STS-0007');

        try {
            $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));
            $this->fail('Expected StsApiResponseException');
        } catch (StsApiResponseException $e) {
            $this->assertSame('kwh must not exceed 1820162.4', $e->getMessage());
            $this->assertSame('kwh', $e->field);
            $this->assertSame(502, $e->render(request())->getStatusCode());
        }
    }

    public function testChargeDeviceRefusesAZeroCreditToken(): void {
        Http::fake();
        $device = $this->stsDevice('STS-0008');

        $this->expectException(StsVendRefusedException::class);

        try {
            $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 0)));
        } finally {
            Http::assertNothingSent();
            $this->assertSame(0, StsTransaction::query()->count());
        }
    }

    public function testChargeDeviceRefusesATariffWithoutAPrice(): void {
        Http::fake();
        $device = $this->stsDevice('STS-0009', pricePerKwh: 0);

        $this->expectException(StsVendRefusedException::class);
        $this->expectExceptionMessage('The tariff of meter STS-0009 has no price per kWh.');

        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));
    }

    public function testChargeDeviceRefusesMoreThanOneTokenCanCarry(): void {
        Http::fake();
        $device = $this->stsDevice('STS-0010', pricePerKwh: 1);

        $this->expectException(StsVendRefusedException::class);
        $this->expectExceptionMessage('can carry at most 1638.3 kWh');

        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 2000)));
    }

    public function testClearDeviceIssuesAClearCreditToken(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::CLEAR_CREDIT_TOKEN])]);
        $device = $this->stsDevice('STS-0011');

        $result = $this->api()->clearDevice($device);

        Http::assertSent(fn (Request $request): bool => $request['type'] === 'CLEAR_CREDIT' && !isset($request['kwh']));
        $this->assertSame(self::CLEAR_CREDIT_TOKEN, $result['token']);
        $this->assertSame(Token::TYPE_RESET, $result['token_type']);
        $this->assertNull($result['token_unit']);
        $this->assertNull($result['token_amount']);

        $issue = StsTransaction::query()->where('token', self::CLEAR_CREDIT_TOKEN)->firstOrFail();
        $this->assertNull($issue->transaction_id);
        $this->assertNull($issue->kwh);
    }

    public function testUnlockIsNotSupported(): void {
        $device = $this->stsDevice('STS-0012');

        $this->expectException(ApiCallDoesNotSupportedException::class);

        $this->api()->unlockDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));
    }

    public function testDeviceInfoReportsWhetherAKeyIsOnFile(): void {
        $withKey = $this->stsDevice('STS-0013');
        $withoutKey = $this->stsDevice('STS-0014', withKey: false);

        $this->assertTrue($this->api()->getDeviceInfo($withKey)['mapped']);
        $this->assertFalse($this->api()->getDeviceInfo($withoutKey)['mapped']);
        $this->assertFalse($this->api()->getDeviceInfo($withoutKey)['device']['key_present']);
    }

    public function testSavingTheTokenLinksItToItsIssueAndRelinksARecreatedToken(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0015');
        $transaction = $this->transaction($device, amount: 500);
        $this->api()->chargeDevice(TransactionDataContainer::initialize($transaction));
        $issue = StsTransaction::query()->where('transaction_id', $transaction->id)->firstOrFail();

        $token = $this->saveToken($device, $transaction, self::TOP_UP_TOKEN);
        $this->assertSame($token->id, $issue->refresh()->token_id);

        $token->delete();
        $this->assertNull($issue->refresh()->token_id);

        $recreated = $this->saveToken($device, $transaction, self::TOP_UP_TOKEN);
        $this->assertSame($recreated->id, $issue->refresh()->token_id);
    }

    public function testSavingAResetTokenBackfillsItsTransaction(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::CLEAR_CREDIT_TOKEN])]);
        $device = $this->stsDevice('STS-0016');
        $this->api()->clearDevice($device);
        $transaction = $this->transaction($device, amount: 0);

        $token = $this->saveToken($device, $transaction, self::CLEAR_CREDIT_TOKEN);

        $issue = StsTransaction::query()->where('token', self::CLEAR_CREDIT_TOKEN)->firstOrFail();
        $this->assertSame($token->id, $issue->token_id);
        $this->assertSame($transaction->id, $issue->transaction_id);
    }

    public function testATokenForAnotherMeterLeavesTheIssueUnlinked(): void {
        Http::fake(['nxt-sts:8080/token' => Http::response(['token' => self::TOP_UP_TOKEN])]);
        $device = $this->stsDevice('STS-0017');
        $otherDevice = $this->stsDevice('STS-0018');
        $this->api()->chargeDevice(TransactionDataContainer::initialize($this->transaction($device, amount: 500)));

        $this->saveToken($otherDevice, $this->transaction($otherDevice, amount: 500), self::TOP_UP_TOKEN);

        $this->assertNull(StsTransaction::query()->where('token', self::TOP_UP_TOKEN)->firstOrFail()->token_id);
    }

    private function api(): StsMeterApi {
        return resolve(StsMeterApi::class);
    }

    private function stsDevice(string $serial, float $pricePerKwh = 100, bool $withKey = true): Device {
        $manufacturer = Manufacturer::query()->firstWhere('api_name', 'CalinMeterApi')
            ?? ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => 'CalinMeterApi']);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => TariffFactory::new()->create(['price' => $pricePerKwh])->id,
            'serial_number' => $serial,
            'vending_provider' => StsMeterApi::VENDING_PROVIDER,
        ]);

        if ($withKey) {
            StsMeter::query()->create(['mpm_meter_id' => $meter->id, 'decoder_key' => self::DECODER_KEY]);
        }

        return DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $serial,
        ]);
    }

    private function stsMeter(Device $device): StsMeter {
        return StsMeter::query()->where('mpm_meter_id', $device->device_id)->firstOrFail();
    }

    private function transaction(Device $device, float $amount): Transaction {
        $cashTransaction = CashTransaction::query()->create(['user_id' => UserFactory::new()->create()->id, 'status' => 1]);

        return TransactionFactory::new()->create([
            'message' => $device->device_serial,
            'amount' => $amount,
            'original_transaction_type' => CashTransaction::RELATION_NAME,
            'original_transaction_id' => $cashTransaction->id,
        ]);
    }

    private function saveToken(Device $device, Transaction $transaction, string $tokenString): Token {
        return Token::query()->create([
            'token' => $tokenString,
            'token_type' => Token::TYPE_ENERGY,
            'transaction_id' => $transaction->id,
            'device_id' => $device->id,
        ]);
    }
}
