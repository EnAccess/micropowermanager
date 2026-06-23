<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class SafaricomMobileMoneyApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        $path = $request->path();

        // Daraja webhook for STK Push result: api/safaricom/webhook/stk-push-result/{companyId}
        if (str_contains($path, 'api/safaricom/webhook/stk-push-result')) {
            return $this->resolveFromStkResultUrl($request);
        }

        // Everything else (credentials, transaction list, STK initiate) is
        // authenticated, so the company comes from the JWT.
        return $this->resolveFromJwt();
    }

    private function resolveFromStkResultUrl(Request $request): int {
        $segments = $request->segments();
        // Expected: [0=api, 1=safaricom, 2=webhook, 3=stk-push-result, 4=companyId]
        if (count($segments) !== 5) {
            throw ValidationException::withMessages(['webhook' => 'Invalid Safaricom STK Push result URL']);
        }

        $companyId = $segments[4];
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['webhook' => 'Invalid company ID in Safaricom STK Push result URL']);
        }

        return (int) $companyId;
    }

    private function resolveFromJwt(): int {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $payload = $guard->check() ? $guard->payload() : null;

        $companyId = $payload?->get('companyId');
        if (!$companyId) {
            throw ValidationException::withMessages(['authentication' => 'Failed to parse company identifier from the request']);
        }

        return (int) $companyId;
    }
}
