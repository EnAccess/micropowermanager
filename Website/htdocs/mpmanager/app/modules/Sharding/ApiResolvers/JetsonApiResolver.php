<?php

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class JetsonApiResolver implements ApiResolverInterface
{
    public function resolveCompanyId(Request $request): int
    {
        $segments = $request->segments();
        if (count($segments) < 6) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $companyId = $segments[5];

        return (int) $companyId;
    }
}
