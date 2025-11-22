<?php

namespace Inensus\Prospect\Http\Clients;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Inensus\Prospect\Services\ProspectCredentialService;

class ProspectApiClient {
    public function __construct(private ProspectCredentialService $credentialService) {}

    /**
     * @param array<string, mixed> $payload
     */
    public function postInstallations(array $payload): Response {
        $credentials = $this->credentialService->getCredentials();
        if (!$credentials || $credentials->isEmpty()) {
            throw new \RuntimeException('Prospect credentials are not configured');
        }

        $cred = $credentials->first(function ($credential) {
            return $credential->api_url && str_contains($credential->api_url, '/installations');
        });

        if (!$cred) {
            $cred = $credentials->first();
        }

        if (!$cred || !$cred->api_url || !$cred->api_token) {
            throw new \RuntimeException('Prospect installations credentials are not configured');
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$cred->api_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post($cred->api_url, $payload);
    }
}
