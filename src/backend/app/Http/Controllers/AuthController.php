<?php

namespace App\Http\Controllers;

use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

#[Group('Auth', 'Responsible for API-Call authentications', weight: 0)]
class AuthController extends Controller {
    /**
     * User login.
     *
     * Login a user of the Web App and get JWT token via given credentials.
     *
     * @bodyParam email string required
     * @bodyParam password string required
     */
    public function login(): JsonResponse {
        $credentials = request(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['data' => ['message' => 'Unauthorized', 'status' => 401]], 401);
        }

        return $this->respondWithToken((string) $token);
    }

    /**
     * Get the authenticated User.
     */
    public function me(): JsonResponse {
        /** @var User */
        $user = auth('api')->user();

        $roles = method_exists($user, 'getRoleNames')
            ? $user->getRoleNames()->toArray()
            : [];
        $permissions = method_exists($user, 'getAllPermissions')
            ? $user->getAllPermissions()->pluck('name')->toArray()
            : [];

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
     * Generates a new valid token for the next 3600 seconds
     * Inorder to generate the new token, a working (Bearer)token has to be provided in the header.
     */
    public function refresh(): JsonResponse {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        return $this->respondWithToken($guard->refresh());
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
