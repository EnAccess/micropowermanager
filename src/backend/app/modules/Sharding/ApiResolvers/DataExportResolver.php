<?php

namespace MPM\Sharding\ApiResolvers;

use App\Exceptions\ValidationException;
use Illuminate\Http\Request;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

class DataExportResolver implements ApiResolverInterface
{
    public function __construct(private DatabaseProxyManagerService $databaseProxyManager)
    {
    }

    public function resolveCompanyId(Request $request): int
    {
        $segments = $request->segments();
        if (count($segments) !== 4) {
            throw new ValidationException('failed to parse company identifier from the webhook');
        }

        $email = $segments[3];
        $databaseProxy = $this->databaseProxyManager->findByEmail($email);
        $companyId = $databaseProxy->getCompanyId();

        return (int) $companyId;
    }
}
