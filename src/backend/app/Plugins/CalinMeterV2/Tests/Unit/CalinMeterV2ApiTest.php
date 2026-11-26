<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeterV2\Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\CalinMeterV2\CalinMeterV2Api;
use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use Database\Factories\DeviceFactory;
use Database\Factories\ManufacturerFactory;
use Database\Factories\Meter\MeterFactory;
use Database\Factories\Meter\MeterTypeFactory;
use Database\Factories\Person\PersonFactory;
use Database\Factories\TariffFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class CalinMeterV2ApiTest extends TestCase {
    use RefreshMultipleDatabases;

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    public function testAddCreditVendsTheTariffEnergyAndRecordsTheUnitsCalinIssued(): void {
        $this->configureCalin($this->envelope(['token' => '4872 5997 6192 9731 1927', 'totalUnit' => 3.2]));
        $container = $this->transactionContainer(amount: 1000, tariffPrice: 300);

        $result = $this->app->make(CalinMeterV2Api::class)->addCredit($container);

        $this->assertSame([
            'token' => '4872 5997 6192 9731 1927',
            'token_type' => Token::TYPE_ENERGY,
            'token_unit' => Token::UNIT_KWH,
            'token_amount' => 3.2,
        ], $result);
        $this->assertSame(3.2, $container->chargeAmount);
        $request = $this->history[1]['request'];
        $this->assertSame('http://calin.example.com:8080/api/token/creditToken/generate', (string) $request->getUri());
        $this->assertSame('Bearer login-token', $request->getHeaderLine('Authorization'));
        $body = json_decode((string) $request->getBody(), true);
        $this->assertSame($container->device?->device->serial_number, $body['meterId']);
        $this->assertFalse($body['isPreview']);
        $this->assertFalse($body['isVendByTotalPaid']);
        $this->assertEqualsWithDelta(1000 / 300, $body['amount'], 0.0001);
        $this->assertSame('calin_v2_transaction', CashTransaction::query()->findOrFail($container->transaction->original_transaction_id)->manufacturer_transaction_type);
    }

    public function testAddCreditFallsBackToTheTariffEnergyWhenCalinReportsNoUnits(): void {
        $this->configureCalin($this->envelope(['token' => 'a-token']));

        $result = $this->app->make(CalinMeterV2Api::class)->addCredit($this->transactionContainer(amount: 1000, tariffPrice: 100));

        $this->assertSame(10.0, $result['token_amount']);
    }

    public function testAddCreditFailsReadablyWhenCalinReturnsNoToken(): void {
        $this->configureCalin($this->envelope(['totalUnit' => 3.2]));

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('returned no token');

        $this->app->make(CalinMeterV2Api::class)->addCredit($this->transactionContainer(amount: 1000, tariffPrice: 100));
    }

    public function testClearCreditReturnsTheTokenAsAClearCreditToken(): void {
        $this->configureCalin($this->envelope(['token' => '9999888877776666']));
        $device = $this->transactionContainer(amount: 0, tariffPrice: 100)->device;

        $result = $this->app->make(CalinMeterV2Api::class)->clearCredit($device);

        $this->assertSame([
            'token' => '9999888877776666',
            'token_type' => Token::TYPE_CLEAR_CREDIT,
            'token_unit' => null,
            'token_amount' => null,
        ], $result);
        $this->assertSame(['meterId' => $device->device_serial], json_decode((string) $this->history[1]['request']->getBody(), true));
    }

    public function testGetDeviceInfoReportsAMeterCalinKnowsAsMapped(): void {
        $this->configureCalin($this->envelope(['total' => 1, 'data' => [[
            'meterId' => '47005247458',
            'type' => 0,
            'protocolVersion' => '1.2',
            'status' => true,
            'sgc' => '600425',
            'krn' => 1,
        ]]]));
        $device = $this->transactionContainer(amount: 0, tariffPrice: 100)->device;

        $info = $this->app->make(CalinMeterV2Api::class)->getDeviceInfo($device);

        $this->assertTrue($info['mapped']);
        $this->assertSame(['meterId' => '47005247458', 'type' => 0, 'protocolVersion' => '1.2', 'status' => true, 'sgc' => '600425'], $info['device']);
    }

    public function testGetDeviceInfoReportsAnUnknownMeterAsNotMapped(): void {
        $this->configureCalin($this->envelope(['total' => 0, 'data' => []]));
        $device = $this->transactionContainer(amount: 0, tariffPrice: 100)->device;

        $this->assertSame(['mapped' => false, 'device' => null], $this->app->make(CalinMeterV2Api::class)->getDeviceInfo($device));
    }

    private function configureCalin(Response ...$responses): void {
        $handler = HandlerStack::create(new MockHandler([$this->envelope(['token' => 'login-token']), ...$responses]));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));

        CalinV2Credential::query()->delete();
        $this->app->make(CalinV2CredentialService::class)->updateCredentials([
            'api_url' => 'http://calin.example.com:8080',
            'company' => null,
            'user_id' => 'user-1',
            'password' => 'secret',
        ]);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function envelope(array $result): Response {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['code' => 0, 'reason' => 'success', 'result' => $result]));
    }

    private function transactionContainer(float $amount, float $tariffPrice): TransactionDataContainer {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => 'CalinMeterV2Api']);
        $tariff = TariffFactory::new()->create(['price' => $tariffPrice]);
        $meter = MeterFactory::new()->create([
            'manufacturer_id' => $manufacturer->id,
            'meter_type_id' => MeterTypeFactory::new()->create()->id,
            'tariff_id' => $tariff->id,
        ]);
        /** @var Device $device */
        $device = DeviceFactory::new()->create([
            'person_id' => PersonFactory::new()->create()->id,
            'device_id' => $meter->id,
            'device_type' => Meter::RELATION_NAME,
            'device_serial' => $meter->serial_number,
        ]);
        $cashTransaction = CashTransaction::query()->create(['user_id' => 1]);
        $transaction = Transaction::query()->create([
            'amount' => $amount,
            'sender' => '+255000000000',
            'message' => $meter->serial_number,
            'type' => 'energy',
            'original_transaction_id' => $cashTransaction->id,
            'original_transaction_type' => CashTransaction::RELATION_NAME,
        ]);

        $container = new TransactionDataContainer();
        $container->transaction = $transaction;
        $container->device = $device;
        $container->tariff = $tariff->fresh();
        $container->amount = $amount;
        $container->chargeAmount = 0;

        return $container;
    }
}
