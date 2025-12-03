<?php

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\JWTGuard;

class DataExportResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        /** @var JWTGuard */
        $guard = auth('api');
        $companyId = $guard->payload()->get('companyId');
        if (is_null($companyId)) {
            throw ValidationException::withMessages(['companyId' => 'failed to parse company identifier from request']);
        }

        return (int) $companyId;
    }
}
