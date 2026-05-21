<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Tests\Unit;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApi;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\RequestTokenResource;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTokenService;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Tests\TestCase;

class PesapalTokenServiceTest extends TestCase {
    public function testReturnsCachedTokenOnSubsequentCalls(): void {
        $cache = new Repository(new ArrayStore());
        $api = $this->createMock(PesapalApi::class);

        $api->expects($this->once())
            ->method('doRequest')
            ->willReturnCallback(function (RequestTokenResource $resource): RequestTokenResource {
                $resource->setBody(json_encode(['token' => 'tok_first', 'expiryDate' => '2099-01-01T00:00:00Z']));

                return $resource;
            });

        $service = new PesapalTokenService($api, $cache);
        $credential = $this->buildCredential();

        $this->assertSame('tok_first', $service->getToken($credential));
        $this->assertSame('tok_first', $service->getToken($credential));
    }

    public function testForgetInvalidatesCachedToken(): void {
        $cache = new Repository(new ArrayStore());
        $api = $this->createMock(PesapalApi::class);

        $tokens = ['tok_first', 'tok_second'];
        $api->expects($this->exactly(2))
            ->method('doRequest')
            ->willReturnCallback(function (RequestTokenResource $resource) use (&$tokens): RequestTokenResource {
                $next = array_shift($tokens);
                $resource->setBody(json_encode(['token' => $next]));

                return $resource;
            });

        $service = new PesapalTokenService($api, $cache);
        $credential = $this->buildCredential();

        $this->assertSame('tok_first', $service->getToken($credential));
        $service->forget($credential);
        $this->assertSame('tok_second', $service->getToken($credential));
    }

    public function testThrowsWhenApiReturnsNoToken(): void {
        $cache = new Repository(new ArrayStore());
        $api = $this->createMock(PesapalApi::class);

        $api->method('doRequest')->willReturnCallback(function (RequestTokenResource $resource): RequestTokenResource {
            $resource->setBody(json_encode(['error' => ['message' => 'invalid_credentials']]));

            return $resource;
        });

        $service = new PesapalTokenService($api, $cache);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('invalid_credentials');

        $service->getToken($this->buildCredential());
    }

    private function buildCredential(): PesapalCredential {
        $credential = new PesapalCredential();
        $credential->id = 1;
        $credential->environment = 'test';
        $credential->setRawAttributes(['id' => 1, 'environment' => 'test'], true);

        return $credential;
    }
}
