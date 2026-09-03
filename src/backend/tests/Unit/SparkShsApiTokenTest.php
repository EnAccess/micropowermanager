<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\SparkShs\Exceptions\SparkShsApiResponseException;
use App\Plugins\SparkShs\Http\Clients\SparkShsApiClient;
use App\Plugins\SparkShs\Models\SparkShsTransaction;
use App\Plugins\SparkShs\Modules\Api\SparkShsApi;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class SparkShsApiTokenTest extends TestCase {
    use MockeryPHPUnitIntegration;

    protected function setUp(): void {
        parent::setUp();
        Event::fake();
    }

    public function testResetDeviceAsksSparkForAResetToken(): void {
        $client = $this->clientReturning(
            'products/kits/SPARK-1/tokens',
            ['type' => 'reset'],
            new GuzzleResponse(201, [], (string) json_encode(
                ['n' => 3, 'token' => '1063061058', 'type' => 'reset', 'expired_at' => 1628232683]
            ))
        );

        $result = new SparkShsApi($this->mockTransactionRecording(), $client)
            ->clearDevice($this->device('SPARK-1'));

        $this->assertSame('1063061058', $result['token']);
        $this->assertSame(Token::TYPE_RESET, $result['token_type']);
        $this->assertNull($result['token_unit']);
        $this->assertNull($result['token_amount']);
    }

    public function testResetDeviceThrowsWhenSparkRejectsTheRequest(): void {
        $client = $this->clientReturning(
            'products/kits/SPARK-3/tokens',
            ['type' => 'reset'],
            new GuzzleResponse(400, [], (string) json_encode(['error' => 'unknown kit']))
        );

        $this->expectException(SparkShsApiResponseException::class);

        new SparkShsApi($this->mockTransactionRecording(), $client)
            ->clearDevice($this->device('SPARK-3'));
    }

    public function testUnlockDeviceAsksSparkForAnUnlockToken(): void {
        $client = $this->clientReturning(
            'products/kits/SPARK-2/tokens',
            ['type' => 'unlock'],
            new GuzzleResponse(201, [], (string) json_encode(
                ['n' => 4, 'token' => '2063061059', 'type' => 'unlock', 'expired_at' => null]
            ))
        );

        $result = new SparkShsApi($this->mockTransactionRecording(), $client)
            ->unlockDevice($this->buildTransactionContainer('SPARK-2'));

        $this->assertSame('2063061059', $result['token']);
        $this->assertSame(Token::TYPE_UNLOCK, $result['token_type']);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function clientReturning(string $path, array $payload, GuzzleResponse $response): SparkShsApiClient {
        /** @var SparkShsApiClient&MockInterface $client */
        $client = \Mockery::mock(SparkShsApiClient::class);
        $client->shouldReceive('post')
            ->once()
            ->with($path, $payload)
            ->andReturn(new Response($response));

        return $client;
    }

    private function mockTransactionRecording(): SparkShsTransaction {
        $manufacturerTransaction = new \stdClass();
        $manufacturerTransaction->id = 1;

        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('create')->with([])->andReturn($manufacturerTransaction);

        /** @var SparkShsTransaction&MockInterface $sparkShsTransaction */
        $sparkShsTransaction = \Mockery::mock(SparkShsTransaction::class);
        $sparkShsTransaction->shouldReceive('newQuery')->andReturn($builder);

        return $sparkShsTransaction;
    }

    private function device(string $serial): Device {
        $device = new Device();
        $device->device_serial = $serial;

        return $device;
    }

    private function buildTransactionContainer(string $deviceSerial): TransactionDataContainer {
        $container = new TransactionDataContainer();

        /** @var Device $device */
        $device = \Mockery::mock(Device::class)->makePartial();
        $device->device_serial = $deviceSerial;
        $container->device = $device;

        /** @var AppliancePerson $appliancePerson */
        $appliancePerson = \Mockery::mock(AppliancePerson::class)->makePartial();
        $container->appliancePerson = $appliancePerson;

        $originalTransaction = \Mockery::mock();
        $originalTransaction->shouldReceive('update')->andReturnNull();

        $morphTo = \Mockery::mock(MorphTo::class);
        $morphTo->shouldReceive('first')->andReturn($originalTransaction);

        $transaction = \Mockery::mock(Transaction::class);
        $transaction->shouldReceive('originalTransaction')->andReturn($morphTo);
        $container->transaction = $transaction;

        return $container;
    }
}
