<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ValidationException;
use App\Jobs\AbstractJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MPM\DatabaseProxy\DatabaseProxyManagerService;
use MPM\Sharding\ApiCompanyResolverService;
use MPM\Sharding\ApiResolvers\Data\ApiResolverMap;

/**
 * The goal is to have the database connection on each incomming http request.
 * This will save querying in each model the correct database connection string
 */
class UserDefaultDatabaseConnectionMiddleware
{
    public function __construct(private DatabaseProxyManagerService $databaseProxyManager,
        private ApiCompanyResolverService $apiCompanyResolverService,
        private ApiResolverMap $apiResolverMap,
    )
    {
    }

    public function handle($request, Closure $next)
    {
        if ($request instanceof Request) {
            return $this->handleApiRequest($request, $next);
        } elseif ($request instanceof AbstractJob) {
            return $this->handleJob($request, $next);
        }
        throw new ValidationException("was not able to handle the request");
    }

    private function handleJob(AbstractJob $job, Closure $next)
    {
        $companyId = $job->getCompanyId();

        return $this->databaseProxyManager->runForCompany($companyId, function () use ($next, $job) {
            return $next($job);
        });
    }

    private function handleApiRequest(Request $request, Closure $next)
    {

        //adding new company should not be proxied. It should use the base database to create the record
        if ($request->path() === 'api/companies' && $request->isMethod('post')) {
            return $next($request);
        }

        //getting mpm plugins should not be proxied. It should use the base database to create the record
        if ($request->path() === 'api/mpm-plugins' && $request->isMethod('get')) {
            return $next($request);
        }
        //webclient login
        if ($request->path() === 'api/auth/login') {
            $databaseProxy = $this->databaseProxyManager->findByEmail($request->input('email'));
            $companyId = $databaseProxy->getCompanyId();
        } elseif ($this->isAgentApp($request->path()) && Str::contains($request->path(), 'login')) { //agent app login
            $databaseProxy = $this->databaseProxyManager->findByEmail($request->input('email'));
            $companyId = $databaseProxy->getCompanyId();
        } elseif ($this->isAgentApp($request->path())) { //agent app authenticated user requests
            $companyId = auth('agent_api')->payload()->get('companyId');
            if (!is_numeric($companyId)) {
                throw new \Exception("JWT is not provided");
            }
        } elseif ($this->resolveThirdPartyApi($request->path())) {
            $companyId = $this->apiCompanyResolverService->resolve($request);
        } else { //web client authenticated user requests
            $companyId = auth('api')->payload()->get('companyId');
            if (!is_numeric($companyId)) {
                throw new \Exception("JWT is not provided");
            }
        }

        return $this->databaseProxyManager->runForCompany($companyId, function () use ($next, $request) {
            return $next($request);
        });
    }

    private function resolveThirdPartyApi(string $requestPath): bool
    {
        foreach ($this->apiResolverMap->getResolvableApis() as $apiPath) {
            if (Str::startsWith(Str::lower($requestPath), Str::lower($apiPath))) {
                return true;
            }
        }
        return false;
    }

    private function isAgentApp(string $path): bool
    {
        return Str::startsWith($path, 'api/app/');
    }

}
