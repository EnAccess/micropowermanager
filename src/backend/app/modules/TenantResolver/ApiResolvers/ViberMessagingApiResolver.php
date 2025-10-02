<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ViberMessagingApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        throw_if(count($segments) !== 4, ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']));

        $companyId = $segments[3];

        return (int) $companyId;
    }
}
