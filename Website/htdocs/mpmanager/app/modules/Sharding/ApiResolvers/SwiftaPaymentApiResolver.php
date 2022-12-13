<?php

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SwiftaPaymentApiResolver implements ApiResolverInterface
{
    public function resolveCompanyId(Request $request): int
    {
        return (int)auth()->payload()->get('companyId');
    }
}