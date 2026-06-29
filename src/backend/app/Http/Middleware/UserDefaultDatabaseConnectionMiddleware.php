<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ApiResolvers\ThirdPartyApiResolverService;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tymon\JWTAuth\JWT;
use Tymon\JWTAuth\JWTGuard;

/**
 * Resolves the company that owns each incoming request and binds its database
 * connection for the duration of the request, so models don't have to look up
 * the connection themselves.
 *
 * Each request type identifies its company a different way; handle() dispatches
 * to the matching strategy in order, falling through to the bearer-token
 * resolver that serves the vast majority of (web client) routes.
 */
class UserDefaultDatabaseConnectionMiddleware {
    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManager,
        private ThirdPartyApiResolverService $thirdPartyApiResolver,
        private JWT $jwt,
    ) {}

    public function handle(Request $request, \Closure $next): SymfonyResponse {
        $path = $request->path();

        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        // Third-party APIs are matched by path prefix and need not be Laravel routes.
        if ($this->thirdPartyApiResolver->matches($path)) {
            return $this->handleForCompany(
                $request,
                $next,
                $this->thirdPartyApiResolver->resolve($request)
            );
        }

        // Every remaining endpoint must be a known Laravel route.
        if (!$this->routeExists($request)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        // Web client login is unauthenticated; the company comes from the email in the body.
        if ($this->isWebLogin($path)) {
            return $this->handleForCompany(
                $request,
                $next,
                $this->companyIdFromLoginEmail($request)
            );
        }

        // Agent app endpoints carry the company in the agent bearer token, except the
        // unauthenticated login / reset endpoints which identify it by email.
        if ($this->isAgentApp($path)) {
            if ($this->isAgentAppLoginOrReset($path)) {
                return $this->handleForCompany(
                    $request,
                    $next,
                    $this->companyIdFromLoginEmail($request)
                );
            }

            return $this->handleForCompany(
                $request,
                $next,
                $this->companyIdFromAgentToken()
            );
        }

        // Standard web client: company comes from the bearer token.
        return $this->handleForCompany(
            $request,
            $next,
            $this->companyIdFromWebClientToken($request)
        );
    }

    private function handleForCompany(Request $request, \Closure $next, int $companyId): SymfonyResponse {
        return $this->databaseProxyManager->runForCompany($companyId, function () use ($next, $request, $companyId) {
            $request->attributes->add(['companyId' => $companyId]);

            return $next($request);
        });
    }

    private function isWebLogin(string $path): bool {
        return $path === 'api/auth/login';
    }

    /**
     * Agent app login / reset are unauthenticated, so they identify the company
     * via the email in the request body rather than the agent bearer token.
     * Only called once the path is known to be an agent app endpoint.
     */
    private function isAgentAppLoginOrReset(string $path): bool {
        return $path === 'api/app/reset-password' || Str::contains($path, 'login');
    }

    private function companyIdFromLoginEmail(Request $request): int {
        try {
            return $this->databaseProxyManager->findByEmail($request->input('email'))->getCompanyId();
        } catch (ModelNotFoundException|NotFoundHttpException) {
            // These email-based paths (web + agent login, password reset) are unauthenticated.
            // An unknown email must fail exactly like a wrong password, otherwise the
            // difference lets an attacker enumerate which emails have accounts in MPM.
            throw new AuthenticationException();
        }
    }

    private function companyIdFromAgentToken(): int {
        /** @var JWTGuard $guard */
        $guard = auth('agent_api');

        return $this->companyIdFromGuard($guard);
    }

    private function companyIdFromWebClientToken(Request $request): int {
        if ($request->path() === 'api/auth/refresh') {
            return $this->companyIdFromRefreshableToken();
        }

        /** @var JWTGuard $guard */
        $guard = auth('api');

        return $this->companyIdFromGuard($guard);
    }

    /**
     * The /api/auth/refresh endpoint must accept expired-but-refreshable tokens,
     * so we decode under tymon's refresh flow there -- signature and refresh_ttl
     * are still enforced, only the exp check is relaxed.
     */
    private function companyIdFromRefreshableToken(): int {
        $manager = $this->jwt->manager();
        $manager->setRefreshFlow(true);
        try {
            $companyId = $this->jwt->parseToken()->getPayload()->get('companyId');
        } finally {
            $manager->setRefreshFlow(false);
        }

        return $this->requireNumericCompanyId($companyId);
    }

    private function companyIdFromGuard(JWTGuard $guard): int {
        return $this->requireNumericCompanyId($guard->payload()->get('companyId'));
    }

    private function requireNumericCompanyId(mixed $companyId): int {
        if (!is_numeric($companyId)) {
            throw new \Exception('JWT is not provided');
        }

        return (int) $companyId;
    }

    private function routeExists(Request $request): bool {
        try {
            Route::getRoutes()->match($request);

            return true;
        } catch (NotFoundHttpException) {
            return false;
        }
    }

    private function isAgentApp(string $path): bool {
        return Str::startsWith($path, 'api/app/');
    }

    private function isExcludedRoute(Request $request): bool {
        $path = $request->path();
        $method = $request->method();

        if (Str::startsWith($path, [
            'horizon',
            'telescope',
            'laravel-erd',
            'api/users/password',
            'api/mpm-plugins',
            'docs/api',
        ])) {
            return true;
        }

        if ($method === 'GET') {
            return in_array($path, [
                'api/micro-star-meters/test',
                'api/protected-pages',
                'api/usage-types',
                'up',
            ]);
        }

        if ($method === 'POST') {
            return $path === 'api/companies';
        }

        return false;
    }
}
