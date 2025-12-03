<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ViberMessagingApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        if (count($segments) !== 4) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
        }

        $companyId = $segments[3];

        return (int) $companyId;
    }
}
