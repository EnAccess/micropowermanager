<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

// test-api passes the company id within the callback url
class TestApiResolver implements ApiResolverInterface {
    private const QUERY_PARAM_COMPANY_ID = 'c';

    public function resolveCompanyId(Request $request): int {
        $companyId = $request->input(self::QUERY_PARAM_COMPANY_ID);
        if (!is_numeric($companyId)) {
            throw ValidationException::withMessages(['companyId' => 'the field which is used to identify the company is not provided']);
        }

        return (int) $companyId;
    }
}
