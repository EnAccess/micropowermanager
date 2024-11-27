<?php

namespace MPM\Sharding\ApiResolvers;

use Illuminate\Http\Request;

class SwiftaPaymentApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        return (int) auth()->payload()->get('companyId');
    }
}
