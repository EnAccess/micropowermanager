<?php

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

class SwiftaPaymentApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return (int) $guard->payload()->get('companyId');
    }
}
