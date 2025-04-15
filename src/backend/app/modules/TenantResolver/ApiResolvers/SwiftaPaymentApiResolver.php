<?php

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

class SwiftaPaymentApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        /** @var JWTGuard $guard */
        $guard = auth('api');

        return (int) $guard->payload()->get('companyId');
    }
}
