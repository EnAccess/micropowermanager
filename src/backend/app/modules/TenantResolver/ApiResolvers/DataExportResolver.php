<?php

namespace MPM\TenantResolver\ApiResolvers;

use App\Exceptions\ValidationException;
use App\Services\CompanyService;
use Illuminate\Http\Request;

class DataExportResolver implements ApiResolverInterface {
    public function __construct(private CompanyService $companyService) {}

    public function resolveCompanyId(Request $request): int {
        $segments = $request->segments();
        if (count($segments) !== 4) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $email = $segments[3];
        $user = $this->companyService->findByEmail($email);
        $companyId = $user->getCompanyId();

        return (int) $companyId;
    }
}
