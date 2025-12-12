<?php

namespace Inensus\Prospect\Http\Clients;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Inensus\Prospect\Services\ProspectCredentialService;

class ProspectApiClient {
    public function __construct(private ProspectCredentialService $credentialService) {}

    /**
     * @param array<string, mixed>                            $payload
     * @param 'installations'|'payments'|'customers'|'agents' $type
     */
    public function postData(array $payload, string $type): Response {
        $credentials = $this->credentialService->getCredentials();
        if (!$credentials || $credentials->isEmpty()) {
            throw new \RuntimeException('Prospect credentials are not configured');
        }

        $cred = $credentials->first(fn ($credential): bool => $credential->api_url && str_contains($credential->api_url, '/'.$type));

        if (!$cred) {
            $cred = $credentials->first();
        }

        if (!$cred->api_url || !$cred->api_token) {
            throw new \RuntimeException("Prospect {$type} credentials are not configured");
        }

        return Http::withHeaders([
            'Authorization' => 'Bearer '.$cred->api_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post($cred->api_url, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function postInstallations(array $payload): Response {
        return $this->postData($payload, 'installations');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function postPayments(array $payload): Response {
        return $this->postData($payload, 'payments');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function postCustomers(array $payload): Response {
        return $this->postData($payload, 'customers');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function postAgents(array $payload): Response {
        return $this->postData($payload, 'agents');
    }
}
