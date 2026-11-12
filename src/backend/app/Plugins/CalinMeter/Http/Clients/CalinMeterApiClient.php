<?php

namespace App\Plugins\CalinMeter\Http\Clients;

use App\Plugins\CalinMeter\Exceptions\CalinApiResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class CalinMeterApiClient {
    private const string CREDIT_TOKEN = 'CreditToken';
    private const string TOKEN_PATH = '/tokennew';
    private const string DEBUG_TOKEN = '48725997619297311927';
    private const string NON_EXISTENT_METER_ID = '00000000000';

    public function __construct(
        private Client $client,
    ) {}

    public function vendCreditToken(string $apiUrl, string $userId, string $apiKey, string $meterId, float $kwh): string {
        if (in_array(config('app.env'), ['demo', 'development'], true)) {
            return self::DEBUG_TOKEN;
        }

        $result = $this->postToken($apiUrl, $userId, $apiKey, $meterId, $kwh);

        if (!is_string($result) && !is_int($result)) {
            throw new CalinApiResponseException('Calin returned a response without a token.');
        }

        return (string) $result;
    }

    /**
     * The token API has no login-only endpoint, so the credentials are sent for a meter that cannot exist.
     * Calin's result codes are undocumented, so instead of matching a specific code the answer is compared
     * with Calin's answer to a password that is certainly wrong: a different answer means the login passed.
     */
    public function checkCredentials(string $apiUrl, string $userId, string $apiKey): void {
        $rejection = $this->rejectionForNonExistentMeter($apiUrl, $userId, $apiKey);

        if (!$rejection instanceof CalinApiResponseException) {
            return;
        }

        $wrongPasswordRejection = $this->rejectionForNonExistentMeter($apiUrl, $userId, bin2hex(random_bytes(16)));

        if (!$wrongPasswordRejection instanceof CalinApiResponseException || $wrongPasswordRejection->resultCode === $rejection->resultCode) {
            throw $rejection;
        }
    }

    private function rejectionForNonExistentMeter(string $apiUrl, string $userId, string $apiKey): ?CalinApiResponseException {
        try {
            $this->postToken($apiUrl, $userId, $apiKey, self::NON_EXISTENT_METER_ID, 0.1);

            return null;
        } catch (CalinApiResponseException $exception) {
            // Without a result code (unreachable host, malformed response) there is nothing to compare.
            if ($exception->resultCode === null) {
                throw $exception;
            }

            return $exception;
        }
    }

    private function postToken(string $apiUrl, string $userId, string $apiKey, string $meterId, float $kwh): mixed {
        $url = rtrim($apiUrl, '/').self::TOKEN_PATH;

        try {
            $response = $this->client->post($url, [
                'json' => [
                    'user_id' => $userId,
                    'password' => $apiKey,
                    'meter_id' => $meterId,
                    'token_type' => self::CREDIT_TOKEN,
                    'amount' => $kwh,
                ],
                'http_errors' => false,
            ]);
        } catch (GuzzleException $exception) {
            Log::critical('Calin API request failed', ['url' => $url, 'message' => $exception->getMessage()]);
            throw new CalinApiResponseException("Could not reach Calin at {$url}: {$exception->getMessage()}", previous: $exception);
        }

        $body = json_decode((string) $response->getBody(), true);

        if (!is_array($body) || !array_key_exists('result_code', $body)) {
            throw new CalinApiResponseException("Calin returned an unexpected response (HTTP {$response->getStatusCode()}). Check the API URL.");
        }

        $resultCode = (int) $body['result_code'];

        if ($resultCode !== 0) {
            $reason = $body['reason'] ?? 'no reason given';
            throw new CalinApiResponseException("Calin rejected the request: {$reason} (code {$resultCode})", $resultCode);
        }

        return $body['result'] ?? null;
    }
}
