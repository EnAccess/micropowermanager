<?php

namespace App\Plugins\AngazaSHS\Tests\Unit;

use App\DTO\TransactionDataContainer;
use App\Models\AppliancePerson;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use App\Plugins\AngazaSHS\Exceptions\AngazaApiResponseException;
use App\Plugins\AngazaSHS\Models\AngazaCredential;
use App\Plugins\AngazaSHS\Models\AngazaTransaction;
use App\Plugins\AngazaSHS\Modules\Api\AngazaSHSApi;
use App\Plugins\AngazaSHS\Modules\Api\ApiRequests;
use App\Plugins\AngazaSHS\Services\AngazaCredentialService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Event;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tests\TestCase;

class AngazaSHSApiTest extends TestCase {
    use MockeryPHPUnitIntegration;

    private AngazaSHSApi $api;
    private AngazaCredentialService&MockInterface $credentialService;
    private AngazaTransaction&MockInterface $angazaTransaction;
    private ApiRequests&MockInterface $apiRequests;

    protected function setUp(): void {
        parent::setUp();
        Event::fake();

        $this->credentialService = \Mockery::mock(AngazaCredentialService::class);
        $this->angazaTransaction = \Mockery::mock(AngazaTransaction::class);
        $this->apiRequests = \Mockery::mock(ApiRequests::class);

        $this->api = new AngazaSHSApi(
            $this->credentialService,
            $this->angazaTransaction,
            $this->apiRequests,
        );
    }

    public function testUnlockDeviceSendsCorrectPayload(): void {
        $transactionContainer = $this->buildTransactionContainer('SERIAL-123');

        $credentials = \Mockery::mock(AngazaCredential::class);
        $this->credentialService->shouldReceive('getCredentials')->once()->andReturn($credentials);

        $this->apiRequests->shouldReceive('put')
            ->once()
            ->withArgs(fn (AngazaCredential $creds, array $params, string $slug): bool => $params['unit_number'] === 'SERIAL-123'
                && $params['state']['desired']['credit_until_dt'] === 'UNLOCKED'
                && $slug === '/unit_credit')
            ->andReturn([
                '_embedded' => [
                    'latest_keycode' => [
                        'keycode' => '1234-5678-9012',
                    ],
                ],
            ]);

        $this->mockTransactionRecording();

        $result = $this->api->unlockDevice($transactionContainer);

        $this->assertSame('1234-5678-9012', $result['token']);
        $this->assertSame(Token::TYPE_UNLOCK, $result['token_type']);
        $this->assertNull($result['token_unit']);
        $this->assertNull($result['token_amount']);
    }

    public function testUnlockDeviceThrowsOnApiError(): void {
        $transactionContainer = $this->buildTransactionContainer('SERIAL-456');

        $credentials = \Mockery::mock(AngazaCredential::class);
        $this->credentialService->shouldReceive('getCredentials')->once()->andReturn($credentials);

        $this->apiRequests->shouldReceive('put')
            ->once()
            ->andReturn([
                'context' => ['reason' => 'Invalid unit number'],
            ]);

        $this->expectException(AngazaApiResponseException::class);
        $this->expectExceptionMessage('Invalid unit number');

        $this->api->unlockDevice($transactionContainer);
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

    private function mockTransactionRecording(): void {
        $manufacturerTransaction = new \stdClass();
        $manufacturerTransaction->id = 1;
        $builder = \Mockery::mock(Builder::class);
        $builder->shouldReceive('create')->with([])->andReturn($manufacturerTransaction);
        $this->angazaTransaction->shouldReceive('newQuery')->andReturn($builder);
    }
}
