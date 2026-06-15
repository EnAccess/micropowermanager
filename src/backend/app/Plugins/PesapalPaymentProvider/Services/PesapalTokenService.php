<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Exceptions\PesapalApiException;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApi;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\RequestTokenResource;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Log;

class PesapalTokenService {
    public function __construct(
        private PesapalApi $api,
        private CacheRepository $cache,
    ) {}

    /**
     * Fetch (or reuse) a bearer token. PesaPal tokens last 5 min; we cache for
     * a slightly shorter window to avoid handing out tokens that expire mid-flight.
     */
    public function getToken(PesapalCredential $credential): string {
        $cacheKey = $this->buildCacheKey($credential);
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $token = $this->fetchFreshToken($credential);
        $ttl = (int) config('pesapal-payment-provider.token_cache_ttl_seconds', 240);
        $this->cache->put($cacheKey, $token, $ttl);

        return $token;
    }

    public function forget(PesapalCredential $credential): void {
        $this->cache->forget($this->buildCacheKey($credential));
    }

    private function fetchFreshToken(PesapalCredential $credential): string {
        $baseUrl = $this->getBaseUrl($credential);
        $resource = new RequestTokenResource($credential, $baseUrl);

        try {
            $response = $this->api->doRequest($resource);
        } catch (GuzzleException|PesapalApiException $exception) {
            Log::error('Pesapal token request failed', [
                'exception_message' => $exception->getMessage(),
                'environment' => $credential->environment,
                'base_url' => $baseUrl,
            ]);
            $detail = $exception->getMessage();
            if ($detail === '') {
                $detail = $exception::class;
            }
            throw new \RuntimeException('Failed to authenticate with PesaPal: '.$detail, 0, $exception);
        }

        /** @var RequestTokenResource $response */
        $token = $response->getToken();
        if (empty($token)) {
            $reason = $response->getError();
            if (empty($reason)) {
                $reason = 'PesaPal RequestToken returned no token. Response: '.substr($response->body, 0, 500);
            }
            Log::error('Pesapal RequestToken returned no token', [
                'environment' => $credential->environment,
                'base_url' => $baseUrl,
                'response_body' => substr($response->body, 0, 1000),
            ]);
            throw new \RuntimeException('Failed to authenticate with PesaPal: '.$reason);
        }

        return $token;
    }

    private function getBaseUrl(PesapalCredential $credential): string {
        $key = $credential->isLive()
            ? 'pesapal-payment-provider.pesapal_api_url_live'
            : 'pesapal-payment-provider.pesapal_api_url_test';

        return (string) config($key);
    }

    private function buildCacheKey(PesapalCredential $credential): string {
        return sprintf('pesapal:token:%d:%s', $credential->id, $credential->environment);
    }
}
