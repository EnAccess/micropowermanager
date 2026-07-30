<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApi;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\AbstractApiResource;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\SubmitOrderRequestResource;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTokenService;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PesapalApiServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    public function testUsesSandboxEndpointForTestCredentials(): void {
        $this->assertStatusEndpoint('test', 'https://sandbox.example.test');
    }

    public function testUsesLiveEndpointForLiveCredentials(): void {
        $this->assertStatusEndpoint('live', 'https://live.example.test');
    }

    public function testCreatesUgxOrderWithReferenceInCallbackUrl(): void {
        config()->set('pesapal-payment-provider.pesapal_api_url_test', 'https://sandbox.example.test');

        $credential = $this->credential('test');
        $credential->currency = 'UGX';
        $credential->callback_url = 'https://frontend.example.test/pay/result?ct=tenant-token';
        $credential->ipn_id = 'ipn-123';

        $transaction = PesapalTransaction::query()->create([
            'amount' => 15000,
            'currency' => 'UGX',
            'order_id' => 'order-123',
            'reference_id' => 'reference-123',
            'status' => PesapalTransaction::STATUS_REQUESTED,
            'customer_id' => 1,
            'serial_id' => 'meter-123',
            'device_type' => 'meter',
        ]);

        $api = $this->createMock(PesapalApi::class);
        $api->expects($this->once())
            ->method('doRequest')
            ->willReturnCallback(function (AbstractApiResource $resource): AbstractApiResource {
                $this->assertInstanceOf(SubmitOrderRequestResource::class, $resource);
                $body = $resource->getBodyData();
                $this->assertSame('UGX', $body['currency']);
                $this->assertSame(15000.0, $body['amount']);
                $this->assertSame(
                    'https://frontend.example.test/pay/result?ct=tenant-token&reference=reference-123',
                    $body['callback_url']
                );
                $resource->body = json_encode([
                    'order_tracking_id' => 'tracking-123',
                    'merchant_reference' => 'reference-123',
                    'redirect_url' => 'https://sandbox.example.test/pay/tracking-123',
                    'status' => 200,
                ]);

                return $resource;
            });

        $service = $this->service($api, $credential);
        $result = $service->submitOrder($transaction, '+256700000000');

        $this->assertNull($result['error']);
        $this->assertSame('tracking-123', $transaction->fresh()->order_tracking_id);
        $this->assertSame('reference-123', $transaction->fresh()->merchant_reference);
    }

    private function assertStatusEndpoint(string $environment, string $expectedBaseUrl): void {
        config()->set('pesapal-payment-provider.pesapal_api_url_test', 'https://sandbox.example.test');
        config()->set('pesapal-payment-provider.pesapal_api_url_live', 'https://live.example.test');

        $credential = $this->credential($environment);
        $api = $this->createMock(PesapalApi::class);
        $api->expects($this->once())
            ->method('doRequest')
            ->willReturnCallback(function (AbstractApiResource $resource) use ($expectedBaseUrl): AbstractApiResource {
                $this->assertStringStartsWith($expectedBaseUrl, $resource->getPaymentUri());
                $resource->body = json_encode([
                    'status_code' => 1,
                    'payment_status_description' => 'COMPLETED',
                    'amount' => 100,
                    'currency' => 'UGX',
                    'confirmation_code' => 'confirmation-123',
                    'merchant_reference' => 'reference-123',
                    'error' => null,
                ]);

                return $resource;
            });

        $result = $this->service($api, $credential)->getTransactionStatus('tracking-123');

        $this->assertNull($result['error']);
        $this->assertSame(1, $result['status_code']);
    }

    private function service(PesapalApi $api, PesapalCredential $credential): PesapalApiService {
        $credentialService = $this->createMock(PesapalCredentialService::class);
        $credentialService->method('getCredentials')->willReturn($credential);

        $tokenService = $this->createMock(PesapalTokenService::class);
        $tokenService->method('getToken')->with($credential)->willReturn('bearer-token');

        return new PesapalApiService($api, $credentialService, $tokenService);
    }

    private function credential(string $environment): PesapalCredential {
        $credential = new PesapalCredential();
        $credential->id = 1;
        $credential->environment = $environment;
        $credential->currency = 'UGX';

        return $credential;
    }
}
