<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Http\Resources\PesapalCredentialResource;
use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTokenService;
use Illuminate\Http\Request;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class PesapalCredentialServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    public function testSavingCredentialsRegistersTenantIpnAndEncryptsSecrets(): void {
        config()->set('app.url', 'https://backend.example.test');

        $request = Request::create('/api/pesapal/credential', 'PUT');
        $request->attributes->set('companyId', 42);
        $this->app->instance('request', $request);

        $tokenService = $this->createMock(PesapalTokenService::class);
        $tokenService->expects($this->once())->method('forget');
        $this->app->instance(PesapalTokenService::class, $tokenService);

        $apiService = $this->createMock(PesapalApiService::class);
        $apiService->expects($this->once())
            ->method('registerIpn')
            ->with(
                $this->isInstanceOf(PesapalCredential::class),
                'https://backend.example.test/api/pesapal/ipn/42',
            )
            ->willReturn(['ipn_id' => 'ipn-tenant-42', 'error' => null]);
        $this->app->instance(PesapalApiService::class, $apiService);

        /** @var PesapalCredentialService $service */
        $service = $this->app->make(PesapalCredentialService::class);
        $credential = $service->updateCredentials([
            'consumer_key' => 'sandbox-consumer-key',
            'consumer_secret' => 'sandbox-consumer-secret',
            'callback_url' => 'https://frontend.example.test/pesapal/result',
            'merchant_name' => 'MPM Uganda',
            'merchant_email' => 'payments@example.test',
            'environment' => 'test',
            'currency' => 'UGX',
        ]);

        $this->assertSame('ipn-tenant-42', $credential->ipn_id);
        $this->assertSame('UGX', $credential->currency);

        $storedCredential = PesapalCredential::query()->findOrFail($credential->id);
        $this->assertNotSame('sandbox-consumer-key', $storedCredential->getRawOriginal('consumer_key'));
        $this->assertNotSame('sandbox-consumer-secret', $storedCredential->getRawOriginal('consumer_secret'));

        $resourceData = new PesapalCredentialResource($credential)->toArray($request);
        $this->assertArrayNotHasKey('consumer_key', $resourceData);
        $this->assertArrayNotHasKey('consumer_secret', $resourceData);
    }
}
