<?php

namespace App\Plugins\SteamaMeter\Tests\Unit;

use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Plugins\SteamaMeter\Exceptions\SteamaApiResponseException;
use App\Plugins\SteamaMeter\Helpers\ApiHelpers;
use App\Plugins\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use App\Plugins\SteamaMeter\Models\SteamaCredential;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SteamaMeterApiClientTest extends TestCase {
    private function createCredential(): void {
        SteamaCredential::query()->create([
            'is_authenticated' => 1,
            'api_url' => 'https://api.steama.co',
            'authentication_token' => Crypt::encryptString('plain-token'),
        ]);
    }

    /**
     * @param array<int, Response> $responses
     */
    private function makeClient(array $responses): SteamaMeterApiClient {
        return new SteamaMeterApiClient(
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            app(ApiHelpers::class),
            new SteamaCredential(),
        );
    }

    public function testGetCredentialsDecryptsTheAuthenticationToken(): void {
        $this->createCredential();

        $credential = app(SteamaMeterApiClient::class)->getCredentials();

        $this->assertSame('plain-token', $credential->authentication_token);
    }

    public function testGetReturnsTheDecodedResponseBody(): void {
        $this->createCredential();
        $client = $this->makeClient([
            new Response(200, [], (string) json_encode(['results' => [['id' => 1]], 'next' => null])),
        ]);

        $this->assertSame(['results' => [['id' => 1]], 'next' => null], $client->get('/sites'));
    }

    public function testGetThrowsWhenApiReturnsAnErrorDetail(): void {
        $this->createCredential();
        $client = $this->makeClient([
            new Response(200, [], (string) json_encode(['detail' => 'Invalid token.'])),
        ]);

        $this->expectException(SteamaApiResponseException::class);
        $client->get('/sites');
    }

    public function testGetThrowsModelNotFoundWhenNoCredentialsExist(): void {
        $client = $this->makeClient([new Response(200, [], '{}')]);

        $this->expectException(ModelNotFoundException::class);
        $client->get('/sites');
    }

    public function testGetAllResultsMergesEveryPage(): void {
        $this->createCredential();
        $client = $this->makeClient([
            new Response(200, [], (string) json_encode([
                'next' => 'https://api.steama.co/sites?page=2&page_size=100',
                'results' => [['id' => 1], ['id' => 2]],
            ])),
            new Response(200, [], (string) json_encode(['next' => null, 'results' => [['id' => 3]]])),
        ]);

        $results = $client->getAllResults('/sites');

        $this->assertSame([1, 2, 3], array_column($results, 'id'));
    }
}
