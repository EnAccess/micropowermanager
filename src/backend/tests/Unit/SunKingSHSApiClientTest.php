<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Plugins\SunKingSHS\Exceptions\SunKingApiResponseException;
use App\Plugins\SunKingSHS\Http\Clients\SunKingSHSApiClient;
use App\Plugins\SunKingSHS\Models\SunKingCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class SunKingSHSApiClientTest extends TestCase {
    public function testGetReturnsNullWhenDeviceNotFound(): void {
        $client = $this->clientReturning(new Response(404, [], json_encode(['message' => 'Device not found'])));

        $result = $client->get($this->credentials(), '/device_details/996995411');

        $this->assertNull($result);
    }

    public function testGetReturnsDecodedBodyWhenDeviceExists(): void {
        $body = ['device' => ['code' => '312', 'name' => 'SK-312 Pro EasyBuy', 'keypad_type' => 2]];
        $client = $this->clientReturning(new Response(200, [], json_encode($body)));

        $result = $client->get($this->credentials(), '/device_details/996995411');

        $this->assertSame($body, $result);
    }

    public function testGetThrowsOnServerError(): void {
        $client = $this->clientReturning(new Response(500, [], json_encode(['message' => 'boom'])));

        $this->expectException(SunKingApiResponseException::class);
        $client->get($this->credentials(), '/device_details/996995411');
    }

    private function clientReturning(Response $response): SunKingSHSApiClient {
        $handler = HandlerStack::create(new MockHandler([$response]));

        return new SunKingSHSApiClient(new Client(['handler' => $handler]));
    }

    private function credentials(): SunKingCredential {
        $credential = new SunKingCredential();
        $credential->api_url = 'https://assetcontrol.central.glpapps.com/v2';
        $credential->access_token = 'test-access-token';

        return $credential;
    }
}
