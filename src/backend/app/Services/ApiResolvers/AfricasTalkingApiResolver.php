<?php

declare(strict_types=1);

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AfricasTalkingApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        if (count(value: $segments) !== 5) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
        }

        $companyId = $segments[3];

        return (int) $companyId;
    }
}
