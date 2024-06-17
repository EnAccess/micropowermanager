<?php

namespace MPM\Sharding\ApiResolvers;

use Carbon\Carbon;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;
use MPM\Sharding\ApiResolvers\ApiResolverInterface;
use Illuminate\Http\Request;

class AirtelPaymentApiResolver implements ApiResolverInterface
{
    private $secretKey = 'KbPeShVmYq3t6w9z$C&F)J@NcQfTjWnZr4u7x!A%D*G-KaPdSgVkXp2s5v8y/B?E';

    public function resolveCompanyId(Request $request): int
    {
        Log::debug('Resolving company ID for Airtel Payment API');
        $authHeader = $request->header('Authorization');

        if (!$this->isValidAuthHeader($authHeader)) {
            throw new \Exception('Authorization header is missing or invalid');
        }

        $token = $this->extractToken($authHeader);

        try {
            $decoded = $this->decodeToken($token);
            $companyId = $this->extractCompanyId($decoded);

            return (int)$companyId;
        } catch (\Exception $e) {
           Log::error('Error while decoding JWT token: ' . $e->getMessage());
            return -1;
        }
    }

    private function isValidAuthHeader($authHeader)
    {
        return $authHeader && str_starts_with($authHeader, 'Bearer ');
    }

    private function extractToken($authHeader)
    {
        return substr($authHeader, 7); // Remove 'Bearer ' from the beginning
    }

    private function decodeToken($token)
    {
        return JWT::decode($token, new Key($this->secretKey, 'HS512'));
    }

    private function extractCompanyId($decoded)
    {
        $decodedArray = (array)$decoded;
        return $decodedArray['sub'] ?? false;
    }
}
