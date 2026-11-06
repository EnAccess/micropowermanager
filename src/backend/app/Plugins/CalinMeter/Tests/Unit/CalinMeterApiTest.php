<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeter\Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\CalinMeter\CalinMeterApi;
use App\Plugins\CalinMeter\Exceptions\CalinApiResponseException;
use App\Plugins\CalinMeter\Models\CalinCredential;
use App\Plugins\CalinMeter\Services\CalinCredentialService;
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

class CalinMeterApiTest extends TestCase {
    use RefreshMultipleDatabases;

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    public function testChargeDeviceRoundsEnergyToOneDecimalAndSendsDecryptedCredentials(): void {
        $this->fakeCalin(new Response(200, [], json_encode(['result_code' => 0, 'reason' => 'OK', 'result' => '11112222333344445555'])));
        $this->app->make(CalinCredentialService::class)->updateCredentials([
            'api_url' => 'http://api.calinhost.com/api',
            'user_id' => 'user-1',
            'api_key' => 'secret',
        ]);
        $container = $this->transactionContainer(amount: 1000, tariffPrice: 300);

        $result = $this->app->make(CalinMeterApi::class)->chargeDevice($container);

        $this->assertSame('11112222333344445555', $result['token']);
        $this->assertSame(3.3, $result['token_amount']);
        $this->assertSame(Token::UNIT_KWH, $result['token_unit']);
        $body = json_decode((string) $this->history[0]['request']->getBody(), true);
        $this->assertSame('user-1', $body['user_id']);
        $this->assertSame('secret', $body['password']);
        $this->assertSame(3.3, $body['amount']);
        $this->assertSame($container->device?->device->serial_number, $body['meter_id']);
        $this->assertSame('calin_transaction', CashTransaction::query()->findOrFail($container->transaction->original_transaction_id)->manufacturer_transaction_type);
    }

    public function testChargeDeviceFailsReadablyWhenCredentialsAreMissing(): void {
        $this->fakeCalin();
        CalinCredential::query()->delete();

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Calin credentials are not configured.');

        $this->app->make(CalinMeterApi::class)->chargeDevice($this->transactionContainer(amount: 1000, tariffPrice: 300));
    }

    private function fakeCalin(Response ...$responses): void {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }

    private function transactionContainer(float $amount, float $tariffPrice): TransactionDataContainer {
        $manufacturer = ManufacturerFactory::new()->create(['type' => 'meter', 'api_name' => 'CalinMeterApi']);
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
