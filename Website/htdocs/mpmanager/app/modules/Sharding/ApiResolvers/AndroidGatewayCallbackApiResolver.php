<?php

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class AndroidGatewayCallbackApiResolver implements ApiResolverInterface
{
    public function resolveCompanyId(Request $request): int
    {
        $segments = $request->segments();
        if (count($segments) !== 5) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $companyId = $segments[4];

        return (int) $companyId;
    }
}
