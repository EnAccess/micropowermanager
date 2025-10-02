<?php

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WaveMoneyApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        throw_if(count($segments) !== 5, ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']));

        $companyId = $segments[4];

        return (int) $companyId;
    }
}
