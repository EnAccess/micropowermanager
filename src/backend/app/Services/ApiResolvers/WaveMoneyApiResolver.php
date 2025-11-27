<?php

namespace App\Services\ApiResolvers;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WaveMoneyApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        if (count($segments) !== 5) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
        }

        $companyId = $segments[4];

        return (int) $companyId;
    }
}
