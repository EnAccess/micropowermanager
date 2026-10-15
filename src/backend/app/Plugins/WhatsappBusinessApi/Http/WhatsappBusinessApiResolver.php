<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Http;

use App\Services\Interfaces\IApiResolver;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WhatsappBusinessApiResolver implements IApiResolver {
    public function resolveCompanyId(Request $request): int {
        $companyId = $request->route('companyId') ?? $request->query('companyId');
        if ($companyId !== null && is_numeric($companyId)) {
            return (int) $companyId;
        }

        $segments = $request->segments();
        foreach ($segments as $segment) {
            if (is_numeric($segment)) {
                return (int) $segment;
            }
        }

        throw ValidationException::withMessages(['webhook' => 'failed to parse company identifier from the webhook']);
    }
}
