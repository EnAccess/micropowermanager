<?php

namespace App\Plugins\AirtelPaymentProvider\Services;

use App\Plugins\AirtelPaymentProvider\Exceptions\AirtelRequestException;

/**
 * Airtel signs every call with a JWT (HS512) that names the transaction the call is about.
 */
class AirtelTokenVerifier {
    private const string ALGORITHM = 'HS512';

    /**
     * @return string the Airtel transaction id the token was issued for
     */
    public function verify(string $token, #[\SensitiveParameter] string $secret): string {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new AirtelRequestException('Invalid Token');
        }

        [$encodedHeader, $encodedClaims, $encodedSignature] = $segments;
        $header = $this->decodeJson($encodedHeader);
        $claims = $this->decodeJson($encodedClaims);

        if (($header['alg'] ?? null) !== self::ALGORITHM) {
            throw new AirtelRequestException('Invalid Token');
        }

        $expectedSignature = hash_hmac('sha512', $encodedHeader.'.'.$encodedClaims, $secret, true);
        if (!hash_equals($expectedSignature, $this->decodeBase64Url($encodedSignature))) {
            throw new AirtelRequestException('Signature verification failed');
        }

        if (!is_numeric($claims['exp'] ?? null) || (int) $claims['exp'] < now()->timestamp) {
            throw new AirtelRequestException('Token has expired');
        }

        $transactionId = $claims['payload']['txnId'] ?? null;
        if (!is_string($transactionId) && !is_int($transactionId)) {
            throw new AirtelRequestException('Invalid Token');
        }

        return (string) $transactionId;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $encoded): array {
        $decoded = json_decode($this->decodeBase64Url($encoded), true);

        if (!is_array($decoded)) {
            throw new AirtelRequestException('Invalid Token');
        }

        return $decoded;
    }

    private function decodeBase64Url(string $encoded): string {
        return (string) base64_decode(strtr($encoded, '-_', '+/'), true);
    }
}
