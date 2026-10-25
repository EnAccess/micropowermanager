<?php

namespace App\Plugins\SparkMeter\Tests\Unit;

use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Helpers\ResultStatusChecker;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCredential;
use App\Plugins\SparkMeter\Models\SmSite;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * `SparkMeterApi::chargeDevice()` now routes its transaction POST through
 * `SparkMeterApiRequests::post()` instead of a raw Guzzle client, specifically
 * so that transport failures surface as `SparkAPIResponseException` (the type
 * its own catch block expects) rather than an uncaught GuzzleException. These
 * tests pin that translation at the shared method every plugin API call goes
 * through, rather than re-testing it once per caller.
 */
class SparkMeterApiRequestsTest extends TestCase {
    private function createSite(): SmSite {
        return SmSite::query()->create([
            'site_id' => 'site-1',
            'mpm_mini_grid_id' => 1,
            'thundercloud_url' => 'https://site-1.sparkmeter.cloud/api/v0',
            'thundercloud_token' => 'a-token',
            'is_authenticated' => 1,
            'is_online' => 1,
        ]);
    }

    /**
     * @param array<int, Response|\Throwable> $responses
     */
    private function makeRequests(array $responses): SparkMeterApiRequests {
        return new SparkMeterApiRequests(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            new ResultStatusChecker(),
            new SmSite(),
            new SmCredential(),
        );
    }

    public function testPostTranslatesAGuzzleTransportFailureIntoSparkAPIResponseException(): void {
        $this->createSite();
        $requests = $this->makeRequests([
            new ConnectException('Connection refused', new Request('POST', '/transaction/')),
        ]);

        $this->expectException(SparkAPIResponseException::class);
        $requests->post('/transaction/', ['amount' => '10'], 'site-1');
    }

    public function testPostReturnsTheDecodedResponseBodyOnSuccess(): void {
        $this->createSite();
        $requests = $this->makeRequests([
            new Response(200, [], (string) json_encode(['transaction_id' => 'txn-1', 'error' => false])),
        ]);

        $result = $requests->post('/transaction/', ['amount' => '10'], 'site-1');

        $this->assertSame('txn-1', $result['transaction_id']);
    }

    public function testPostThrowsWhenTheApiBodyReportsAnError(): void {
        $this->createSite();
        $requests = $this->makeRequests([
            new Response(200, [], (string) json_encode(['error' => 'no such customer'])),
        ]);

        $this->expectException(SparkAPIResponseException::class);
        $requests->post('/transaction/', ['amount' => '10'], 'site-1');
    }

    public function testGetFromKoiosSendsTheDecryptedApiKeyAndSecret(): void {
        SmCredential::query()->create([
            'api_url' => 'https://koios.test/api/v0',
            'api_key' => Crypt::encryptString('plain-key'),
            'api_secret' => Crypt::encryptString('plain-secret'),
        ]);
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], (string) json_encode(['error' => null, 'organizations' => []])),
        ]));
        $handler->push(Middleware::history($history));
        $requests = new SparkMeterApiRequests(
            new Client(['handler' => $handler]),
            new ResultStatusChecker(),
            new SmSite(),
            new SmCredential(),
        );

        $requests->getFromKoios('/organizations');

        $this->assertSame('plain-key', $history[0]['request']->getHeaderLine('X-API-KEY'));
        $this->assertSame('plain-secret', $history[0]['request']->getHeaderLine('X-API-SECRET'));
    }
}
