<?php

declare(strict_types=1);

namespace App\Plugins\OpenPaygo\Tests\Unit;

use App\Plugins\OpenPaygo\Exceptions\OpenPaygoGeneratorException;
use App\Plugins\OpenPaygo\Http\Clients\OpenPaygoGeneratorClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenPaygoGeneratorClientTest extends TestCase {
    private const string BASE_URL = 'http://openpaygo-generator:3000';
    private const string API_KEY = 'test-openpaygo-generator-key-0123456789';

    protected function setUp(): void {
        parent::setUp();

        config()->set('services.openpaygo_generator.url', self::BASE_URL);
        config()->set('services.openpaygo_generator.api_key', self::API_KEY);
        config()->set('services.openpaygo_generator.connect_timeout', 2);
        config()->set('services.openpaygo_generator.timeout', 8);
    }

    public function testItSendsTheConfiguredJsonRequestAndPreservesTheTokenAndCounter(): void {
        $payload = [
            'secretKeyHex' => str_repeat('a', 32),
            'startingCode' => 516959010,
            'counter' => 1,
            'tokenType' => 'ADD_TIME',
            'value' => 1,
            'restrictedDigitSet' => false,
        ];
        Http::fake([
            self::BASE_URL.'/generate' => Http::response([
                'token' => '000588224011',
                'nextCounter' => 2,
            ]),
        ]);

        $result = (new OpenPaygoGeneratorClient())->generateToken($payload);

        $this->assertSame(['token' => '000588224011', 'nextCounter' => 2], $result);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::BASE_URL.'/generate'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer '.self::API_KEY)
            && $request->hasHeader('Content-Type', 'application/json')
            && $request->data() === $payload);
    }

    public function testItMapsGeneratorValidationErrorsWithoutReturningTheResponseBody(): void {
        Http::fake([
            self::BASE_URL.'/generate' => Http::response(['error' => 'secret-bearing validation detail'], 422),
        ]);

        try {
            (new OpenPaygoGeneratorClient())->generateToken(['secretKeyHex' => str_repeat('b', 32)]);
            $this->fail('Expected the generator validation response to fail.');
        } catch (OpenPaygoGeneratorException $exception) {
            $this->assertSame('OpenPAYGO generator returned HTTP 422.', $exception->getMessage());
            $this->assertSame(502, $exception->render(HttpRequest::create('/'))->getStatusCode());
            $this->assertStringNotContainsString('secret-bearing', $exception->getMessage());
            $this->assertStringNotContainsString(self::API_KEY, $exception->getMessage());
        }
    }

    public function testItMapsServerErrorsWithoutReturningTheResponseBody(): void {
        Http::fake([
            self::BASE_URL.'/generate' => Http::response(['error' => 'internal details'], 503),
        ]);

        try {
            (new OpenPaygoGeneratorClient())->generateToken([]);
            $this->fail('Expected the generator server response to fail.');
        } catch (OpenPaygoGeneratorException $exception) {
            $this->assertSame('OpenPAYGO generator returned HTTP 503.', $exception->getMessage());
            $this->assertStringNotContainsString('internal details', $exception->getMessage());
        }
    }

    public function testItMapsConnectionFailuresWithoutExposingRequestData(): void {
        Http::fake(static function (): never {
            throw new ConnectionException('Connection refused');
        });

        try {
            (new OpenPaygoGeneratorClient())->generateToken(['secretKeyHex' => str_repeat('c', 32)]);
            $this->fail('Expected the connection failure to be mapped.');
        } catch (OpenPaygoGeneratorException $exception) {
            $this->assertSame('OpenPAYGO generator request failed.', $exception->getMessage());
            $this->assertStringNotContainsString(str_repeat('c', 32), $exception->getMessage());
        }
    }

    public function testItMapsTimeoutsWithoutExposingRequestData(): void {
        Http::fake(static function (): never {
            throw new ConnectionException('Connection timed out');
        });

        try {
            (new OpenPaygoGeneratorClient())->generateToken(['secretKeyHex' => str_repeat('d', 32)]);
            $this->fail('Expected the timeout to be mapped.');
        } catch (OpenPaygoGeneratorException $exception) {
            $this->assertSame('OpenPAYGO generator request failed.', $exception->getMessage());
            $this->assertStringNotContainsString(str_repeat('d', 32), $exception->getMessage());
        }
    }

    public function testItRejectsMalformedJsonResponses(): void {
        Http::fake([
            self::BASE_URL.'/generate' => Http::response('{invalid-json', 200),
        ]);

        $this->expectException(OpenPaygoGeneratorException::class);
        $this->expectExceptionMessage('OpenPAYGO generator returned invalid JSON.');

        (new OpenPaygoGeneratorClient())->generateToken([]);
    }

    public function testItRejectsMissingOrIncorrectResponseFields(): void {
        Http::fakeSequence(self::BASE_URL.'/generate')
            ->push(['nextCounter' => 2], 200)
            ->push(['token' => 123, 'nextCounter' => 2], 200)
            ->push(['token' => '000123', 'nextCounter' => '2'], 200)
            ->push(['token' => '000123'], 200);

        foreach (range(1, 4) as $_) {
            try {
                (new OpenPaygoGeneratorClient())->generateToken([]);
                $this->fail('Expected malformed generator response fields to fail.');
            } catch (OpenPaygoGeneratorException $exception) {
                $this->assertSame('OpenPAYGO generator returned an invalid response.', $exception->getMessage());
            }
        }
    }
}
