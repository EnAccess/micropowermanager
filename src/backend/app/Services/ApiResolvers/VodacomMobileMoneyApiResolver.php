<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class VodacomMobileMoneyApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        /** @var JWTGuard $guard */
        $guard = auth('api');
        $payload = $guard->check() ? $guard->payload() : null;

        $companyId = $payload?->get('companyId');

        if (!$companyId) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
        }

        return (int) $companyId;
    }
}
