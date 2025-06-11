<?php

namespace Inensus\SafaricomMobileMoney\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inensus\SafaricomMobileMoney\Models\SafaricomSettings;

class SafaricomAuthService {
    private const CACHE_KEY = 'safaricom_access_token';
    private const CACHE_TTL = 3500; // 1 hour - 100 seconds buffer

    public function __construct(
        private SafaricomSettings $settings,
    ) {}

    /**
     * Get a valid access token for Safaricom API.
     *
     * @return string
     */
    public function getAccessToken(): string {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return $this->generateAccessToken();
        });
    }

    /**
     * Generate a new access token from Safaricom API.
     *
     * @return string
     *
     * @throws \Exception
     */
    private function generateAccessToken(): string {
        $settings = $this->settings->query()->first();
        if (!$settings) {
            throw new \Exception('Safaricom settings not configured');
        }

        $baseUrl = config('safaricom-mobile-money.api.base_url');
        $consumerKey = $settings->consumer_key;
        $consumerSecret = $settings->consumer_secret;

        $credentials = base64_encode($consumerKey.':'.$consumerSecret);

        $response = Http::withHeaders([
            'Authorization' => 'Basic '.$credentials,
            'Content-Type' => 'application/json',
        ])->get($baseUrl.'/oauth/v1/generate?grant_type=client_credentials');

        if (!$response->successful()) {
            throw new \Exception('Failed to generate access token: '.$response->body());
        }

        $data = $response->json();
        if (!isset($data['access_token'])) {
            throw new \Exception('Invalid response from Safaricom API');
        }

        return $data['access_token'];
    }

    /**
     * Clear the cached access token.
     *
     * @return void
     */
    public function clearAccessToken(): void {
        Cache::forget(self::CACHE_KEY);
    }
}
