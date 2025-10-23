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
        $cred = $this->credentialService->getCredentials();
        if (!$cred || !$cred->api_url || !$cred->api_token) {
            throw new \RuntimeException('Prospect credentials are not configured');
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$cred->api_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post($cred->api_url, $payload);
    }
}
