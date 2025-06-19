<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VodacomMobileMoneyApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $payload = $guard->check() ? $guard->payload() : null;

        $companyId = $payload?->get('companyId');

        if (!$companyId) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
        }

        return (int) $companyId;
    }
}
