<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Services;

use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafaricomAuthService {
    private const int CACHE_TTL = 3500; // 1 hour - 100 seconds buffer

    public function getAccessToken(SafaricomCredential $credential): string {
        return Cache::remember(
            $this->cacheKey($credential->environment),
            self::CACHE_TTL,
            fn () => $this->generateAccessToken($credential),
        );
    }

    public function clearAccessToken(): void {
        Cache::forget($this->cacheKey('sandbox'));
        Cache::forget($this->cacheKey('production'));
    }

    private function generateAccessToken(SafaricomCredential $credential): string {
        $consumerKey = $credential->consumer_key ?? '';
        $consumerSecret = $credential->consumer_secret ?? '';

        if ($consumerKey === '' || $consumerSecret === '') {
            throw new \RuntimeException('Safaricom consumer key/secret are missing — open the Credentials page and save them.');
        }

        $url = $this->getBaseUrl($credential).'/oauth/v1/generate?grant_type=client_credentials';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic '.base64_encode($consumerKey.':'.$consumerSecret),
                'Content-Type' => 'application/json',
            ])->get($url);
        } catch (\Throwable $e) {
            Log::error('Safaricom OAuth network error', [
                'environment' => $credential->environment,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not reach Daraja for token: '.$e->getMessage(), $e->getCode(), $e);
        }

        if (!$response->successful()) {
            $status = $response->status();
            $bodyExcerpt = substr($response->body(), 0, 400);
            Log::error('Safaricom OAuth rejected', [
                'environment' => $credential->environment,
                'url' => $url,
                'status' => $status,
                'body_excerpt' => $bodyExcerpt,
            ]);

            $reason = match (true) {
                $status === 400 || $status === 401 => 'Daraja rejected the consumer key/secret. Re-check them on the Credentials page (sandbox vs production matters).',
                $status >= 500 => "Daraja's OAuth gateway returned {$status} — try again in a moment.",
                default => "Daraja OAuth returned HTTP {$status}.",
            };
            throw new \RuntimeException($reason);
        }

        $token = $response->json('access_token');
        if (!is_string($token) || $token === '') {
            Log::error('Safaricom OAuth missing access_token', [
                'body_excerpt' => substr($response->body(), 0, 400),
            ]);
            throw new \RuntimeException('Daraja returned no access_token.');
        }

        return $token;
    }

    private function getBaseUrl(SafaricomCredential $credential): string {
        return $credential->isProduction()
            ? (string) config('safaricom-ke-payment-provider.api.production_url', 'https://api.safaricom.co.ke')
            : (string) config('safaricom-ke-payment-provider.api.sandbox_url', 'https://sandbox.safaricom.co.ke');
    }

    private function cacheKey(string $environment): string {
        return 'safaricom:access_token:'.$environment;
    }
}
