<?php

namespace MPM\TenantResolver\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class DataExportResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        if (is_null($companyId)) {
            throw new ValidationException('failed to parse company identifier from request');
        }

        return (int) $companyId;
    }
}
