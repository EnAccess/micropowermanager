<?php

declare(strict_types=1);

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;

// test-api passes the company id within the callback url
class TestApiResolver implements ApiResolverInterface
{

    private const QUERY_PARAM_COMPANY_ID = 'c';

    public function resolveCompanyId(Request $request): int
    {
        $companyId = $request->input(self::QUERY_PARAM_COMPANY_ID);
        if(!is_numeric($companyId)) {
            throw new ValidationException('the field which is used to identify the company is not provided');
        }
       return (int)$companyId;
    }

}
