<?php

declare(strict_types=1);

namespace App\Plugins\CalinMeter\Tests\Unit;

use App\Plugins\CalinMeter\Exceptions\CalinApiResponseException;
use App\Plugins\CalinMeter\Http\Clients\CalinMeterApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Tests\TestCase;

class CalinMeterApiClientTest extends TestCase {
    private const string API_URL = 'http://api.calinhost.com/api';

    /** @var list<array{request: Request}> */
    private array $history = [];

    public function testVendCreditTokenPostsTheTokenRequestAndReturnsTheToken(): void {
        $client = $this->clientReturning($this->calinResponse(['result_code' => 0, 'reason' => 'OK', 'result' => '12345678901234567890']));

        $token = $client->vendCreditToken(self::API_URL.'/', 'user-1', 'secret', '47000268748', 3.3);

        $this->assertSame('12345678901234567890', $token);
        $request = $this->history[0]['request'];
        $this->assertSame(self::API_URL.'/tokennew', (string) $request->getUri());
        $this->assertSame([
            'user_id' => 'user-1',
            'password' => 'secret',
            'meter_id' => '47000268748',
            'token_type' => 'CreditToken',
            'amount' => 3.3,
        ], json_decode((string) $request->getBody(), true));
    }

    public function testVendCreditTokenReturnsTheDebugTokenInDevelopmentWithoutCallingCalin(): void {
        config()->set('app.env', 'development');
        $client = $this->clientReturning();

        $this->assertSame('48725997619297311927', $client->vendCreditToken(self::API_URL, 'user-1', 'secret', '47000268748', 1.0));
        $this->assertSame([], $this->history);
    }

    public function testNonZeroResultCodeBecomesAReadableException(): void {
        $client = $this->clientReturning($this->calinResponse(['result_code' => 4, 'reason' => 'Password error', 'result' => null]));

        try {
            $client->vendCreditToken(self::API_URL, 'user-1', 'wrong', '47000268748', 1.0);
            $this->fail('Expected CalinApiResponseException');
        } catch (CalinApiResponseException $exception) {
            $this->assertSame('Calin rejected the request: Password error (code 4)', $exception->getMessage());
            $this->assertSame(4, $exception->resultCode);
        }
    }

    public function testHtmlResponseIsReportedAsUnexpected(): void {
        $client = $this->clientReturning(new Response(404, ['Content-Type' => 'text/html'], '<html>Not Found</html>'));

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Calin returned an unexpected response (HTTP 404). Check the API URL.');

        $client->vendCreditToken(self::API_URL, 'user-1', 'secret', '47000268748', 1.0);
    }

    public function testJsonWithoutResultCodeIsReportedAsUnexpected(): void {
        $client = $this->clientReturning($this->calinResponse(['error' => 'something']));

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Calin returned an unexpected response (HTTP 200)');

        $client->vendCreditToken(self::API_URL, 'user-1', 'secret', '47000268748', 1.0);
    }

    public function testSuccessfulResponseWithoutATokenIsRejected(): void {
        $client = $this->clientReturning($this->calinResponse(['result_code' => 0, 'reason' => 'OK']));

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Calin returned a response without a token.');

        $client->vendCreditToken(self::API_URL, 'user-1', 'secret', '47000268748', 1.0);
    }

    public function testConnectionFailureNamesTheUrl(): void {
        $client = $this->clientReturning(new ConnectException('Connection refused', new Request('POST', self::API_URL.'/tokennew')));

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Could not reach Calin at '.self::API_URL.'/tokennew');

        $client->vendCreditToken(self::API_URL, 'user-1', 'secret', '47000268748', 1.0);
    }

    public function testCheckCredentialsPassesWhenTheLoginGetsFurtherThanAWrongPassword(): void {
        $client = $this->clientReturning(
            $this->calinResponse(['result_code' => 7, 'reason' => 'Meter not exist', 'result' => null]),
            $this->calinResponse(['result_code' => 4, 'reason' => 'Password error', 'result' => null]),
        );

        $client->checkCredentials(self::API_URL, 'user-1', 'secret');

        $this->assertCount(2, $this->history);
        $this->assertSame('secret', $this->sentPassword(0));
        $this->assertNotSame('secret', $this->sentPassword(1));
    }

    public function testCheckCredentialsReportsCalinsReasonWhenTheKeyIsAnsweredLikeAWrongPassword(): void {
        $client = $this->clientReturning(
            $this->calinResponse(['result_code' => 4, 'reason' => 'Password error', 'result' => null]),
            $this->calinResponse(['result_code' => 4, 'reason' => 'Password error', 'result' => null]),
        );

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Calin rejected the request: Password error (code 4)');

        $client->checkCredentials(self::API_URL, 'user-1', 'wrong');
    }

    public function testCheckCredentialsReportsCalinsReasonForAnUnknownUser(): void {
        $client = $this->clientReturning(
            $this->calinResponse(['result_code' => 3, 'reason' => 'User not exist', 'result' => null]),
            $this->calinResponse(['result_code' => 3, 'reason' => 'User not exist', 'result' => null]),
        );

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('User not exist');

        $client->checkCredentials(self::API_URL, 'nobody', 'secret');
    }

    public function testCheckCredentialsPassesWithoutASecondRequestWhenCalinAcceptsTheRequest(): void {
        $client = $this->clientReturning($this->calinResponse(['result_code' => 0, 'reason' => 'OK', 'result' => '1234']));

        $client->checkCredentials(self::API_URL, 'user-1', 'secret');

        $this->assertCount(1, $this->history);
    }

    public function testCheckCredentialsReportsAnUnreachableHostWithoutASecondRequest(): void {
        $client = $this->clientReturning(new ConnectException('Connection refused', new Request('POST', self::API_URL.'/tokennew')));

        $this->expectException(CalinApiResponseException::class);
        $this->expectExceptionMessage('Could not reach Calin');

        $client->checkCredentials(self::API_URL, 'user-1', 'secret');
    }

    private function clientReturning(ResponseInterface|ConnectException ...$responses): CalinMeterApiClient {
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));

        return new CalinMeterApiClient(new Client(['handler' => $handler]));
    }

    private function sentPassword(int $requestIndex): mixed {
        $payload = json_decode((string) $this->history[$requestIndex]['request']->getBody(), true);

        return is_array($payload) ? $payload['password'] ?? null : null;
    }

    /**
     * @param array<string, mixed> $body
     */
    private function calinResponse(array $body): ResponseInterface {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode($body));
    }
}
