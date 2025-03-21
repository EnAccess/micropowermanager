<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class AfricasTalkingApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        if (count($segments) !== 5) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $companyId = $segments[3];

        return (int) $companyId;
    }
}
