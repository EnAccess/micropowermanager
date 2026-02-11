<?php

namespace App\Plugins\SparkShs\Http\Clients;

use App\Plugins\SparkShs\Exceptions\SparkShsUnsafeAuthRequestException;
use App\Plugins\SparkShs\Services\SparkShsCredentialService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SparkShsApiClient {
    public function __construct(
        private SparkShsCredentialService $credentialService,
    ) {}

    /**
     * Get a valid access token.
     * Checks DB first, then (re-)authenticates if needed.
     */
    public function getToken(): string {
        if ($this->hasValidToken()) {
            return $this->credentialService->getCredentials()->access_token;
        }

        return $this->authenticate();
    }

    /**
     * Checks if current token exists and is not expired.
     */
    protected function hasValidToken(): bool {
        $credentials = $this->credentialService->getCredentials();

        return $credentials->access_token
            && $credentials->access_token_expires_at
            && Carbon::now()->lt($credentials->access_token_expires_at);
    }

    protected function checkUrl($url): void {
        $parts = parse_url($url);

        if (($parts['scheme'] ?? null) !== 'https') {
            throw new SparkShsUnsafeAuthRequestException("Only HTTPS URLs allowed. {$url} given.");
        }

        if (empty($parts['host'])) {
            throw new SparkShsUnsafeAuthRequestException("URL invalid: {$url}");
        }

        $ip = gethostbyname($parts['host']);

        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            throw new SparkShsUnsafeAuthRequestException("Invalid target IP address for: {$url}");
        }
    }

    /**
     * Authenticate with credentials and store access token in DB.
     */
    protected function authenticate(): string {
        $credentials = $this->credentialService->getCredentials();

        $urls = [
            'auth_url' => $credentials->auth_url,
            'api_url' => $credentials->api_url,
        ];

        foreach ($urls as $name => $url) {
            $this->checkUrl($url);
        }

        // Remove version segment from the end of the path
        $audience = preg_replace('#/v\d+/?$#', '', rtrim($credentials->api_url, '/'));

        $response = Http::asJson()
            ->post($credentials->auth_url, [
                'client_id' => $credentials->client_id,
                'client_secret' => $credentials->client_secret,
                'audience' => $audience,
                'grant_type' => 'client_credentials',
            ]);

        $data = $response->json();

        if (!isset($data['access_token']) || !isset($data['expires_in'])) {
            throw new \Exception('Failed to authenticate with Spark SHS API');
        }

        $this->credentialService->updateCredentials([
            'access_token' => $data['access_token'],
            'access_token_expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return $data['access_token'];
    }

    /**
     * Build full URL from API root + path.
     */
    protected function buildUrl(string $path): string {
        $credentials = $this->credentialService->getCredentials();
        $apiRoot = rtrim($credentials->api_url, '/');
        $path = ltrim($path, '/');

        $url = "{$apiRoot}/{$path}";

        $this->checkUrl($url);

        return $url;
    }

    /**
     * GET request to API with Bearer token.
     */
    public function get(string $path, array $query = [], array $headers = []) {
        $url = $this->buildUrl($path);

        return Http::withHeaders(array_merge($headers, [
            'Authorization' => 'Bearer '.$this->getToken(),
        ]))->get($url, $query);
    }

    /**
     * POST request to API with Bearer token.
     */
    public function post(string $path, array $data = [], array $headers = []) {
        $url = $this->buildUrl($path);

        return Http::withHeaders(array_merge($headers, [
            'Authorization' => 'Bearer '.$this->getToken(),
        ]))->post($url, $data);
    }

    /**
     * PUT request to API with Bearer token.
     */
    public function put(string $path, array $data = [], array $headers = []) {
        $url = $this->buildUrl($path);

        return Http::withHeaders(array_merge($headers, [
            'Authorization' => 'Bearer '.$this->getToken(),
        ]))->put($url, $data);
    }
}
