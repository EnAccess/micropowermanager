<?php

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;

class SwiftaPaymentApiResolver implements ApiResolverInterface {
    /**
     * @method \Tymon\JWTAuth\Payload payload()
     */
    public function resolveCompanyId(Request $request): int {
        return (int) auth()->payload()->get('companyId');
    }
}
