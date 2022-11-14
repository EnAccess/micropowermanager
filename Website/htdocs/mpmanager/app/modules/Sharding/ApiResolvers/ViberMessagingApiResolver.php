<?php

declare(strict_types=1);

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

// test-api passes the company id within the callback url
class ViberMessagingApiResolver implements ApiResolverInterface
{

    public function resolveCompanyId(Request $request): int
    {
        $segments = $request->segments();
        if(count($segments) !== 4) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $companyId = $segments[3];

       return (int)$companyId;
    }

}
