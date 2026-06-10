<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Plugins\PesapalPaymentProvider\Services\PesapalCompanyHashService;
use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class PesapalApiResolver implements IApiResolver {
    public function __construct(
        private PesapalCompanyHashService $hashService,
    ) {}

    public function resolveCompanyId(Request $request): int {
        $path = $request->path();

        // PesaPal IPN: /api/pesapal/ipn/{companyId}
        if (str_contains($path, 'api/pesapal/ipn')) {
            return $this->resolveFromIpnUrl($request);
        }

        // Public payment pages: /api/pesapal/public/{type}/{companyHash}
        if (str_contains($path, 'api/pesapal/public')) {
            $token = $request->query('ct');
            if ($token) {
                $cid = $this->hashService->parseHashFromCompanyId($token);
                if (is_int($cid)) {
                    return $cid;
                }
            }

            return $this->resolveFromPublicUrl($request);
        }

        return $this->resolveFromJWT();
    }

    private function resolveFromIpnUrl(Request $request): int {
        $segments = $request->segments();

        // Expected: [0=api, 1=pesapal, 2=ipn, 3=companyId]
        if (count($segments) !== 4) {
            throw ValidationException::withMessages(['ipn' => 'failed to parse company identifier from Pesapal IPN URL']);
        }

        $companyId = $segments[3];
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['ipn' => 'invalid company ID in Pesapal IPN URL']);
        }

        return (int) $companyId;
    }

    private function resolveFromPublicUrl(Request $request): int {
        $segments = $request->segments();

        // Expected: [0=api, 1=pesapal, 2=public, 3=type, 4=companyHash, 5=companyId]
        if (count($segments) < 6) {
            throw ValidationException::withMessages(['public_url' => 'Invalid public payment URL format']);
        }

        $companyId = $segments[5];

        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['public_url' => 'Invalid company ID in public payment URL']);
        }

        return (int) $companyId;
    }

    private function resolveFromJWT(): int {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['authentication' => 'JWT token required for Pesapal API access']);
        }

        return (int) $companyId;
    }
}
