<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaystackApiResolver implements ApiResolverInterface {
    public function __construct(
    ) {}

    public function resolveCompanyId(Request $request): int {
        // For webhook callbacks, get company ID from URL segments
        if ($request->isMethod('POST') && str_contains($request->path(), 'api/paystack/webhook')) {
            return $this->resolveFromWebhookUrl($request);
        }

        // For other Paystack API calls, try to get from JWT token
        return $this->resolveFromJWT($request);
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

    private function resolveFromJWT(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        $companyId = $guard->payload()->get('companyId');
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['authentication' => 'JWT token required for Paystack API access']);
        }

        return (int) $companyId;
    }
}
