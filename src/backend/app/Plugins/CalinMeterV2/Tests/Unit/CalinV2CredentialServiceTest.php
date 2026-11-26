<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeterV2\Tests\Unit;

use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use App\Plugins\CalinMeterV2\Services\CalinV2CredentialService;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Tests\RefreshMultipleDatabases;
use Tests\TestCase;

class CalinV2CredentialServiceTest extends TestCase {
    use RefreshMultipleDatabases;

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    public function testUpdateCredentialsLogsInAndStoresEverythingEncrypted(): void {
        $this->fakeCalin($this->loginResponse('fresh-token'));
        CalinV2Credential::query()->delete();

        $credential = $this->service()->updateCredentials($this->credentialData());

        $this->assertSame(1, CalinV2Credential::query()->count());
        $this->assertSame('user-1', $credential->user_id);
        $this->assertSame('fresh-token', $credential->access_token);
        $stored = CalinV2Credential::query()->firstOrFail();
        foreach (['company' => 'ACME', 'user_id' => 'user-1', 'password' => 'secret', 'access_token' => 'fresh-token'] as $field => $plain) {
            $this->assertNotSame($plain, $stored->getRawOriginal($field));
        }
    }

    public function testUpdateCredentialsStoresTheCorrectedApiAddress(): void {
        $this->fakeCalin($this->loginResponse('fresh-token'));

        $credential = $this->service()->updateCredentials(['api_url' => 'http://calin.example.com:8080/api'] + $this->credentialData());

        $this->assertSame('http://calin.example.com:8080', $credential->api_url);
    }

    public function testUpdateCredentialsKeepsTheStoredCredentialsWhenCalinRejectsTheLogin(): void {
        $this->fakeCalin($this->loginResponse('first-token'), new Response(200, [], (string) json_encode(['code' => 1, 'reason' => 'Password error', 'result' => null])));
        $service = $this->service();
        $service->updateCredentials($this->credentialData());

        try {
            $service->updateCredentials(['password' => 'wrong'] + $this->credentialData());
            $this->fail('Expected CalinV2ApiResponseException');
        } catch (CalinV2ApiResponseException $exception) {
            $this->assertSame('Calin rejected the request: Password error (code 1)', $exception->getMessage());
        }

        $this->assertSame('secret', $service->getCredentials()?->password);
    }

    public function testAuthenticatedCredentialsReusesALiveAccessToken(): void {
        $this->fakeCalin($this->loginResponse('first-token'));
        $service = $this->service();
        $service->updateCredentials($this->credentialData());

        $this->assertSame('first-token', $service->authenticatedCredentials()->access_token);
        $this->assertCount(1, $this->history);
    }

    public function testAuthenticatedCredentialsLogsInAgainOnceTheAccessTokenExpired(): void {
        $this->fakeCalin($this->loginResponse('first-token'), $this->loginResponse('second-token'));
        $service = $this->service();
        $service->updateCredentials($this->credentialData());
        CalinV2Credential::query()->update(['token_expires_in' => time() - 1]);

        $credential = $service->authenticatedCredentials();

        $this->assertSame('second-token', $credential->access_token);
        $this->assertCount(2, $this->history);
        $stored = CalinV2Credential::query()->firstOrFail();
        $this->assertNotSame('second-token', $stored->getRawOriginal('access_token'));
        $this->assertNotSame('secret', $stored->getRawOriginal('password'));
        $this->assertSame('second-token', $service->getCredentials()?->access_token);
    }

    public function testAuthenticatedCredentialsFailsReadablyWhenNothingIsConfigured(): void {
        $this->fakeCalin();
        $this->service()->createCredentials();

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('Calin API V2 credentials are not configured.');

        $this->service()->authenticatedCredentials();
    }

    private function service(): CalinV2CredentialService {
        return $this->app->make(CalinV2CredentialService::class);
    }

    /**
     * @return array{api_url: string, company: ?string, user_id: string, password: string}
     */
    private function credentialData(): array {
        return [
            'api_url' => 'http://calin.example.com:8080',
            'company' => 'ACME',
            'user_id' => 'user-1',
            'password' => 'secret',
        ];
    }

    private function loginResponse(string $token): Response {
        return new Response(200, [], (string) json_encode(['code' => 0, 'reason' => 'success', 'result' => ['token' => $token]]));
    }

    private function fakeCalin(Response ...$responses): void {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));
        $this->app->instance(Client::class, new Client(['handler' => $handler]));
    }
}
