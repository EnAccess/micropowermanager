<?php

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DataExportResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        if (is_null($companyId)) {
            throw ValidationException::withMessages(['companyId' => 'failed to parse company identifier from request']);
        }

        return (int) $companyId;
    }
}
