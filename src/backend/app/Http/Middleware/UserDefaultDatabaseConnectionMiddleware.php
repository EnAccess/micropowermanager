<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use MPM\DatabaseProxy\DatabaseProxyManagerService;
use MPM\TenantResolver\ApiCompanyResolverService;
use MPM\TenantResolver\ApiResolvers\Data\ApiResolverMap;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The goal is to have the database connection on each incomming http request.
 * This will save querying in each model the correct database connection string.
 */
class UserDefaultDatabaseConnectionMiddleware {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManager,
        private ApiCompanyResolverService $apiCompanyResolverService,
        private ApiResolverMap $apiResolverMap,
    ) {}

    public function handle(Request $request, \Closure $next): SymfonyResponse {
        // skip middleware for excluded routes
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // skip middleware for third party api requests
        if ($this->resolveThirdPartyApi($request->path())) {
            return $this->resolveRoute($request, $next);
        }

        // Attempt to match against known Laravel routes
        try {
            Route::getRoutes()->match($request);
        } catch (NotFoundHttpException) {
            // Path doesn't exist in Laravel routes or third-party API map
            return response()->json(['message' => 'Not found'], 404);
        }

        return $this->resolveRoute($request, $next);
    }

    private function resolveRoute(Request $request, \Closure $next): SymfonyResponse {
        // webclient login
        if ($request->path() === 'api/auth/login' || $request->path() === 'api/app/login') {
            $databaseProxy = $this->databaseProxyManager->findByEmail($request->input('email'));
            $companyId = $databaseProxy->getCompanyId();
        } elseif ($request->path() === 'api/users/password' && $request->isMethod('post')) {
            $databaseProxy = $this->databaseProxyManager->findByEmail($request->input('email'));
            $companyId = $databaseProxy->getCompanyId();
        } elseif ($this->isAgentApp($request->path()) && Str::contains($request->path(), 'login')) { // agent app login
            $databaseProxy = $this->databaseProxyManager->findByEmail($request->input('email'));
            $companyId = $databaseProxy->getCompanyId();
        } elseif ($this->isAgentApp($request->path())) { // agent app authenticated user requests
            /** @var \Tymon\JWTAuth\JWTGuard */
            $guard = auth('agent_api');
            $companyId = $guard->payload()->get('companyId');
            if (!is_numeric($companyId)) {
                throw new \Exception('JWT is not provided');
            }
        } elseif ($this->resolveThirdPartyApi($request->path())) {
            $companyId = $this->apiCompanyResolverService->resolve($request);
        } else { // web client authenticated user requests
            /** @var \Tymon\JWTAuth\JWTGuard */
            $guard = auth('api');
            $companyId = $guard->payload()->get('companyId');
            if (!is_numeric($companyId)) {
                throw new \Exception('JWT is not provided');
            }
        }

        return $this->databaseProxyManager->runForCompany($companyId, function () use ($next, $request) {
            return $next($request);
        });
    }

    private function resolveThirdPartyApi(string $requestPath): bool {
        foreach ($this->apiResolverMap->getResolvableApis() as $apiPath) {
            if (Str::startsWith(Str::lower($requestPath), Str::lower($apiPath))) {
                return true;
            }
        }

        return false;
    }

    private function isAgentApp(string $path): bool {
        return Str::startsWith($path, 'api/app/');
    }

    private function isExcludedRoute(Request $request): bool {
        $path = $request->path();
        $method = $request->method();

        if ($method === 'GET') {
            return in_array($path, [
                'api/micro-star-meters/test',
                'api/mpm-plugins',
                'api/protected-pages',
                'api/usage-types',
                'up',
            ]);
        }

        if ($method === 'POST') {
            return $path === 'api/companies';
        }

        if (Str::startsWith($path, [
            'horizon',
            'laravel-erd',
        ])) {
            return true;
        }

        return false;
    }
}
