<?php

namespace App\Plugins\CalinMeterV2\Http\Clients;

use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiPermissionException;
use App\Plugins\CalinMeterV2\Exceptions\CalinV2ApiResponseException;
use App\Plugins\CalinMeterV2\Models\CalinV2Credential;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

/**
 * Speaks Calin's API V2: a JWT login followed by bearer-authenticated calls, each
 * answering with a `{code, reason, result}` envelope. Callers pass the decrypted
 * credential in, so credential storage and transport stay separate.
 */
class CalinMeterV2ApiClient {
    private const string LOGIN_PATH = '/api/user/login';

    /**
     * Calin's spec types the envelope code as a bare integer and enumerates nothing.
     * A live system answers `0` with the reason "success" on every call, so that is
     * the only value treated as success.
     */
    private const int SUCCESS_CODE = 0;

    private const int FALLBACK_TOKEN_LIFETIME_SECONDS = 3600;

    /**
     * A role refusal arrives as a bare 403 with an empty body, not as an envelope.
     */
    private const int HTTP_FORBIDDEN = 403;

    private const string NOT_AN_API_V2_HINT = 'If you log in at ami.calinhost.com with a company name and vending password, use the Calin Smart Meter plugin.';

    public function __construct(
        private Client $client,
    ) {}

    /**
     * Operators often enter the address of Calin's web dashboard, which runs on another
     * port than the API. The dashboard names its API in its own page, so when the entered
     * address does not answer like the API, the login is retried at the dashboard's API.
     * The address that worked is returned for the caller to store.
     *
     * @return array{api_url: string, access_token: string, token_expires_in: int}
     *
     * @throws CalinV2ApiResponseException
     */
    public function login(CalinV2Credential $credential): array {
        $payload = [
            'userId' => $credential->user_id,
            'password' => $credential->password,
        ];

        // Only some installations ask for a company name at login.
        if ($credential->company !== null && $credential->company !== '') {
            $payload['company'] = $credential->company;
        }

        $apiUrl = $this->normalizedApiUrl((string) $credential->api_url);
        $response = $this->send($apiUrl.self::LOGIN_PATH, $payload);

        $dashboardApiUrl = $this->envelopeOf($response) === null ? $this->apiUrlOfDashboard($apiUrl) : null;
        if ($dashboardApiUrl !== null) {
            $apiUrl = $dashboardApiUrl;
            $response = $this->send($apiUrl.self::LOGIN_PATH, $payload);
        }

        $result = $this->readEnvelope($apiUrl.self::LOGIN_PATH, $response, self::NOT_AN_API_V2_HINT);
        $token = $result['token'] ?? null;

        if (!is_string($token) || $token === '') {
            throw new CalinV2ApiResponseException('Calin accepted the login but returned no access token.');
        }

        return [
            'api_url' => $apiUrl,
            'access_token' => $token,
            'token_expires_in' => $this->expiryOf($token),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<mixed>
     *
     * @throws CalinV2ApiResponseException
     */
    public function post(CalinV2Credential $credential, string $path, array $payload): array {
        $url = $this->url($credential, $path);
        $response = $this->send($url, $payload, ['Authorization' => 'Bearer '.$credential->access_token]);

        if ($response->getStatusCode() === self::HTTP_FORBIDDEN) {
            throw new CalinV2ApiPermissionException("The Calin user is not permitted to call {$path}. Ask Calin to grant this user the matching role.");
        }

        return $this->readEnvelope($url, $response);
    }

    private function url(CalinV2Credential $credential, string $path): string {
        return $this->normalizedApiUrl((string) $credential->api_url).$path;
    }

    /**
     * Every endpoint path already starts with /api, so a pasted address ending in /api
     * would double it.
     */
    private function normalizedApiUrl(string $apiUrl): string {
        return (string) preg_replace('#/api$#i', '', rtrim(trim($apiUrl), '/'));
    }

    private function apiUrlOfDashboard(string $dashboardUrl): ?string {
        try {
            $page = (string) $this->client->get($dashboardUrl, ['http_errors' => false])->getBody();
        } catch (GuzzleException) {
            return null;
        }

        if (preg_match('/ApiConfig\s*=\s*\{\s*Url\s*:\s*["\']([^"\']+)["\']/', $page, $matches) !== 1) {
            return null;
        }

        $apiUrl = $this->normalizedApiUrl($matches[1]);

        return $apiUrl === $dashboardUrl ? null : $apiUrl;
    }

    /**
     * @return array<mixed>|null
     */
    private function envelopeOf(ResponseInterface $response): ?array {
        $body = json_decode((string) $response->getBody(), true);
        $response->getBody()->rewind();

        return is_array($body) && array_key_exists('code', $body) ? $body : null;
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    private function send(string $url, array $payload, array $headers = []): ResponseInterface {
        try {
            return $this->client->post($url, [
                'json' => $payload,
                'headers' => ['Accept' => 'application/json'] + $headers,
                'http_errors' => false,
            ]);
        } catch (GuzzleException $exception) {
            Log::critical('Calin API V2 request failed', ['url' => $url, 'message' => $exception->getMessage()]);
            throw new CalinV2ApiResponseException("Could not reach Calin at {$url}: {$exception->getMessage()}", $exception->getCode(), previous: $exception);
        }
    }

    /**
     * @return array<mixed>
     *
     * @throws CalinV2ApiResponseException
     */
    private function readEnvelope(string $url, ResponseInterface $response, string $unexpectedResponseHint = ''): array {
        $body = $this->envelopeOf($response);

        if ($body === null) {
            throw new CalinV2ApiResponseException(trim("Calin returned an unexpected response (HTTP {$response->getStatusCode()}) from {$url}. Check the API URL. {$unexpectedResponseHint}"));
        }

        $code = $body['code'];
        Log::debug('Calin API V2 responded', ['url' => $url, 'code' => $code]);

        if ($code !== self::SUCCESS_CODE) {
            $reason = $body['reason'] ?? 'no reason given';
            throw new CalinV2ApiResponseException("Calin rejected the request: {$reason} (code ".var_export($code, true).')');
        }

        $result = $body['result'] ?? null;

        return is_array($result) ? $result : [];
    }

    /**
     * Login answers with the token alone, so its lifetime is read from the JWT's own
     * `exp` claim. A token without a readable claim is treated as short-lived.
     */
    private function expiryOf(string $token): int {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            return time() + self::FALLBACK_TOKEN_LIFETIME_SECONDS;
        }

        $payload = json_decode((string) base64_decode(strtr($segments[1], '-_', '+/'), true), true);

        if (!is_array($payload) || !isset($payload['exp']) || !is_numeric($payload['exp'])) {
            return time() + self::FALLBACK_TOKEN_LIFETIME_SECONDS;
        }

        return (int) $payload['exp'];
    }
}
