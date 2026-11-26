<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeterV2\Tests\Unit;

use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiPermissionException;
use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Http\Clients\CalinMeterV2ApiClient;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class CalinMeterV2ApiClientTest extends TestCase {
    private const string API_URL = 'http://calin.example.com:8080';

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    public function testLoginSendsTheCredentialsAndReadsTheTokenExpiry(): void {
        $expiry = time() + 7200;
        $token = 'header.'.rtrim(strtr(base64_encode((string) json_encode(['exp' => $expiry])), '+/', '-_'), '=').'.signature';
        $client = $this->clientReturning($this->envelope(0, ['token' => $token]));

        $login = $client->login($this->credential());

        $this->assertSame(['api_url' => self::API_URL, 'access_token' => $token, 'token_expires_in' => $expiry], $login);
        $request = $this->history[0]['request'];
        $this->assertSame(self::API_URL.'/api/user/login', (string) $request->getUri());
        $this->assertSame(['userId' => 'user-1', 'password' => 'secret'], json_decode((string) $request->getBody(), true));
    }

    public function testLoginSendsTheCompanyWhenOneIsSet(): void {
        $client = $this->clientReturning($this->envelope(0, ['token' => 'a-token']));

        $client->login($this->credential(['company' => 'ACME']));

        $this->assertSame('ACME', json_decode((string) $this->history[0]['request']->getBody(), true)['company']);
    }

    public function testLoginFallsBackToAShortLifetimeWhenTheTokenCarriesNoExpiry(): void {
        $client = $this->clientReturning($this->envelope(0, ['token' => 'not-a-jwt']));

        $login = $client->login($this->credential());

        $this->assertGreaterThan(time(), $login['token_expires_in']);
        $this->assertLessThanOrEqual(time() + 3600, $login['token_expires_in']);
    }

    public function testLoginWithoutATokenIsRejected(): void {
        $client = $this->clientReturning($this->envelope(0, []));

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('Calin accepted the login but returned no access token.');

        $client->login($this->credential());
    }

    public function testLoginAgainstAnAddressThatIsNotApiV2PointsToTheCalinSmartMeterPlugin(): void {
        $client = $this->clientReturning(
            new Response(404, ['Content-Type' => 'text/html'], '<html>Not Found</html>'),
            new Response(404, ['Content-Type' => 'text/html'], '<html>Not Found</html>'),
        );

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('Calin returned an unexpected response (HTTP 404) from '.self::API_URL.'/api/user/login. Check the API URL. If you log in at ami.calinhost.com with a company name and vending password, use the Calin Smart Meter plugin.');

        $client->login($this->credential());
    }

    public function testLoginAtTheDashboardAddressIsRetriedAtTheApiTheDashboardNames(): void {
        $client = $this->clientReturning(
            new Response(404, ['Content-Type' => 'text/html'], '<html>Not Found</html>'),
            new Response(200, ['Content-Type' => 'text/html'], '<script>var ApiConfig = {
        Url: "http://203.0.113.10:5300"
      }</script>'),
            $this->envelope(0, ['token' => 'a-token']),
        );

        $login = $client->login($this->credential(['api_url' => 'http://203.0.113.10:5301']));

        $this->assertSame('http://203.0.113.10:5300', $login['api_url']);
        $this->assertSame('http://203.0.113.10:5301/api/user/login', (string) $this->history[0]['request']->getUri());
        $this->assertSame('GET', $this->history[1]['request']->getMethod());
        $this->assertSame('http://203.0.113.10:5300/api/user/login', (string) $this->history[2]['request']->getUri());
    }

    public function testLoginDropsATrailingApiFromTheAddress(): void {
        $client = $this->clientReturning($this->envelope(0, ['token' => 'a-token']));

        $login = $client->login($this->credential(['api_url' => self::API_URL.'/api/']));

        $this->assertSame(self::API_URL, $login['api_url']);
        $this->assertSame(self::API_URL.'/api/user/login', (string) $this->history[0]['request']->getUri());
    }

    public function testPostSendsTheBearerTokenAndUnwrapsTheResult(): void {
        $client = $this->clientReturning($this->envelope(0, ['token' => '1234']));

        $result = $client->post($this->credential(['access_token' => 'jwt']), '/api/token/creditToken/generate', ['meterId' => '1']);

        $this->assertSame(['token' => '1234'], $result);
        $request = $this->history[0]['request'];
        $this->assertSame('Bearer jwt', $request->getHeaderLine('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function testNonZeroCodeBecomesAReadableException(): void {
        $client = $this->clientReturning($this->envelope(1001, null, 'Meter not exist'));

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('Calin rejected the request: Meter not exist (code 1001)');

        $client->post($this->credential(['access_token' => 'jwt']), '/api/token/creditToken/generate', []);
    }

    public function testForbiddenCallIsReportedAsAPermissionProblem(): void {
        $client = $this->clientReturning(new Response(403));

        $this->expectException(CalinV2ApiPermissionException::class);
        $this->expectExceptionMessage('The Calin user is not permitted to call /api/meter/read.');

        $client->post($this->credential(['access_token' => 'jwt']), '/api/meter/read', []);
    }

    public function testConnectionFailureNamesTheUrl(): void {
        $client = $this->clientReturning(new ConnectException('Connection refused', new Request('POST', self::API_URL)));

        $this->expectException(CalinV2ApiResponseException::class);
        $this->expectExceptionMessage('Could not reach Calin at '.self::API_URL.'/api/user/login');

        $client->login($this->credential());
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function credential(array $attributes = []): CalinV2Credential {
        return new CalinV2Credential($attributes + [
            'api_url' => self::API_URL.'/',
            'user_id' => 'user-1',
            'password' => 'secret',
        ]);
    }

    private function clientReturning(ResponseInterface|ConnectException ...$responses): CalinMeterV2ApiClient {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));

        return new CalinMeterV2ApiClient(new Client(['handler' => $handler]));
    }

    private function envelope(int $code, mixed $result, string $reason = 'success'): ResponseInterface {
        return new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'code' => $code,
            'reason' => $reason,
            'result' => $result,
        ]));
    }
}
