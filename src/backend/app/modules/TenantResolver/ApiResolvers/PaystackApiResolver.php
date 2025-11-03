<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Tymon\JWTAuth\JWTGuard;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;

class PaystackApiResolver implements ApiResolverInterface {
    public function __construct(
        private PaystackCompanyHashService $hashService,
    ) {}

    public function resolveCompanyId(Request $request): int {
        // For webhook callbacks, get company ID from URL segments
        if ($request->isMethod('POST') && str_contains($request->path(), 'api/paystack/webhook')) {
            return $this->resolveFromWebhookUrl($request);
        }

        // For public payment pages, get company ID from URL segments or token
        if (str_contains($request->path(), 'api/paystack/public')) {
            // Prefer token param if present
            $token = $request->query('ct');
            if ($token) {
                $cid = $this->hashService->parseHashFromCompanyId($token);
                if (is_int($cid)) {
                    return $cid;
                }
            }

            return $this->resolveFromPublicUrl($request);
        }

        // For other Paystack API calls, try to get from JWT token
        return $this->resolveFromJWT();
    }

    private function resolveFromWebhookUrl(Request $request): int {
        $segments = $request->segments();

        // Expected URL: api/paystack/webhook/{companyId}
        // Segments: [0=api, 1=paystack, 2=webhook, 3=companyId]
        if (count($segments) !== 4) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from Paystack webhook URL']);
        }

        $companyId = $segments[3];

        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['webhook' => 'invalid company ID in Paystack webhook URL']);
        }

        return (int) $companyId;
    }

    private function resolveFromPublicUrl(Request $request): int {
        $segments = $request->segments();

        // Expected URL: api/paystack/public/{type}/{companyHash}/{companyId}
        // Segments: [0=api, 1=paystack, 2=public, 3=type, 4=companyHash, 5=companyId]
        if (count($segments) < 6) {
            throw ValidationException::withMessages(['public_url' => 'Invalid public payment URL format']);
        }

        $companyId = $segments[5];

        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['public_url' => 'Invalid company ID in public payment URL']);
        }

        return (int) $companyId;
    }

    private function resolveFromJWT(): int
    {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['authentication' => 'JWT token required for Paystack API access']);
        }
        return (int) $companyId;
    }
}
