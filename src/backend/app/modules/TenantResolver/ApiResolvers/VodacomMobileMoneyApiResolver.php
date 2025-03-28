<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

class VodacomMobileMoneyApiResolver implements ApiResolverInterface {
    public function resolveCompanyId(Request $request): int {
        $companyId = auth('api')->payload()->get('companyId');

        if (!$companyId) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        return (int) $companyId;
    }
}
