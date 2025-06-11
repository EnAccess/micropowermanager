<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class SafaricomMobileMoneyApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');
        $payload = $guard->check() ? $guard->payload() : null;

        $companyId = $payload?->get('companyId');

        if (!$companyId) {
            throw new ValidationException('Failed to parse company identifier from the request');
        }

        return (int) $companyId;
    }
}
