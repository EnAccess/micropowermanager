<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Utils\DemoCompany;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

#[Group('Auth', 'Responsible for API-Call authentications', weight: 0)]
class AuthController extends Controller {
    /**
     * User login.
     *
     * Login a user of the Web App and get JWT token via given credentials.
     */
    #[BodyParameter('email', type: 'string', format: 'email', example: DemoCompany::DEMO_COMPANY_ADMIN_EMAIL)]
    #[BodyParameter('password', type: 'string', format: 'password', example: DemoCompany::DEMO_COMPANY_PASSWORD)]
    public function login(): JsonResponse {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            throw new AuthenticationException('');
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse {
        $user = auth('api')->user();

        if (method_exists($user, 'getRoleNames')) {
            /** @var User $user */
            $roles = $user->getRoleNames()->toArray();
        } else {
            $roles = [];
        }
        if (method_exists($user, 'getAllPermissions')) {
            /** @var User $user */
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        } else {
            $permissions = [];
        }

        return response()->json([
            /* @var User */
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * User logout.
     *
     * Logout the user and invalidate the JWT token.
     */
    public function logout(): JsonResponse {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh User token.
     *
     * Rotates the bearer token in the request for a fresh one. Accepts expired tokens within JWT_REFRESH_TTL.
     * Rejects tokens past that window or with an invalid signature.
     */
    public function refresh(): JsonResponse {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');
        $newToken = $guard->refresh();
        // refresh() rotates the token but does not populate the guard's user
        // cache. The route runs without auth:api so we authenticate with the
        // freshly issued token before respondWithToken reads the user.
        $guard->setToken($newToken)->authenticate();

        return $this->respondWithToken($newToken);
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     */
    protected function respondWithToken($token): JsonResponse {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        /** @var User $user */
        $user = auth('api')->user();
        $roles = $user->getRoleNames()->toArray();
        $permissions = $user->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
            'user' => $user,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
