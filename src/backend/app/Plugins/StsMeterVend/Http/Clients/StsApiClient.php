<?php

namespace App\Plugins\StsMeterVend\Http\Clients;

use App\Plugins\StsMeterVend\Enums\StsBaseDate;
use App\Plugins\StsMeterVend\Enums\StsEncryptionAlgorithm;
use App\Plugins\StsMeterVend\Exceptions\StsApiResponseException;
use App\Plugins\StsMeterVend\Models\StsMeter;
use App\Plugins\StsMeterVend\Models\StsTransaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * nxt-sts is a stateless, unauthenticated token engine reached only over the
 * private network, so the URL is deployment configuration rather than a tenant
 * credential. Every failure is reported as 502: MPM builds every request
 * itself, so even an engine 4xx is a fault upstream of the MPM caller. This is
 * the only code that knows which key profiles the engine vends and how its
 * requests are shaped, so an engine upgrade changes this class alone.
 */
class StsApiClient {
    /** @var list<StsBaseDate> */
    private const array SUPPORTED_BASE_DATES = [StsBaseDate::Year2014];
    /** @var list<StsEncryptionAlgorithm> */
    private const array SUPPORTED_ENCRYPTION_ALGORITHMS = [StsEncryptionAlgorithm::Sta];
    /** @var list<int> */
    private const array SUPPORTED_KEY_EXPIRY_NUMBERS = [255];

    public function supportsKeyProfile(StsBaseDate $baseDate, StsEncryptionAlgorithm $encryptionAlgorithm, int $keyExpiryNumber): bool {
        return in_array($baseDate, self::SUPPORTED_BASE_DATES, true)
            && in_array($encryptionAlgorithm, self::SUPPORTED_ENCRYPTION_ALGORITHMS, true)
            && in_array($keyExpiryNumber, self::SUPPORTED_KEY_EXPIRY_NUMBERS, true);
    }

    public function generateToken(StsMeter $stsMeter, StsTransaction $issue): string {
        $payload = [
            'type' => $issue->token_type,
            'issueDate' => $issue->issued_at->format('Y-m-d\TH:i:s'),
            'randomNumber' => $issue->random_number,
            'decoderKey' => $stsMeter->decoder_key,
            ...($issue->kwh === null ? [] : ['kwh' => $issue->kwh]),
        ];

        $response = $this->send(fn (PendingRequest $request) => $request->post('/token', $payload));

        return (string) $response->json('token');
    }

    /**
     * @return array<string, mixed>
     */
    public function serviceInfo(): array {
        return $this->send(fn (PendingRequest $request) => $request->get('/'))->json();
    }

    /**
     * @param \Closure(PendingRequest): Response $call
     */
    private function send(\Closure $call): Response {
        $baseUrl = (string) config('services.sts.url');
        if ($baseUrl === '') {
            throw new StsApiResponseException('STS_API_URL is not configured.');
        }

        try {
            $response = $call(Http::baseUrl($baseUrl)->acceptJson()->timeout(5));
        } catch (ConnectionException $e) {
            throw new StsApiResponseException("STS service unreachable: {$e->getMessage()}");
        }

        if ($response->successful()) {
            return $response;
        }

        $message = (string) ($response->json('error') ?? "STS service responded with HTTP {$response->status()}");

        throw new StsApiResponseException($message, $response->json('field'));
    }
}
