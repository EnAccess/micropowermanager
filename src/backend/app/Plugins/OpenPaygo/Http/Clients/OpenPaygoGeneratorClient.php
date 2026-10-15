<?php

namespace App\Plugins\OpenPaygo\Http\Clients;

use App\Plugins\OpenPaygo\Exceptions\OpenPaygoGeneratorException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class OpenPaygoGeneratorClient {
    public function assertConfigured(): void {
        $baseUrl = config('services.openpaygo_generator.url');
        $apiKey = config('services.openpaygo_generator.api_key');
        if (!is_string($baseUrl) || trim($baseUrl) === '') {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator URL is not configured.');
        }
        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator API key is not configured.');
        }
        if ((int) config('services.openpaygo_generator.connect_timeout') < 1
            || (int) config('services.openpaygo_generator.timeout') < 1) {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator timeouts must be positive.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{token: string, nextCounter: int}
     */
    public function generateToken(array $payload): array {
        $this->assertConfigured();
        $baseUrl = trim((string) config('services.openpaygo_generator.url'));
        $apiKey = (string) config('services.openpaygo_generator.api_key');
        $connectTimeout = (int) config('services.openpaygo_generator.connect_timeout');
        $timeout = (int) config('services.openpaygo_generator.timeout');

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withToken($apiKey)
                ->connectTimeout($connectTimeout)
                ->timeout($timeout)
                ->post(rtrim($baseUrl, '/').'/generate', $payload);
        } catch (ConnectionException) {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator request failed.');
        }

        if (!$response->successful()) {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator returned HTTP '.$response->status().'.');
        }

        try {
            $responseData = json_decode($response->body(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator returned invalid JSON.');
        }

        if (!is_array($responseData)
            || !array_key_exists('token', $responseData)
            || !is_string($responseData['token'])
            || !array_key_exists('nextCounter', $responseData)
            || !is_int($responseData['nextCounter'])) {
            throw new OpenPaygoGeneratorException('OpenPAYGO generator returned an invalid response.');
        }

        return [
            'token' => $responseData['token'],
            'nextCounter' => $responseData['nextCounter'],
        ];
    }
}
