<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\JWTGuard;

/**
 * @group   Authenticator
 * Class AuthController
 * Responsible for API-Call authentications.
 */
class AuthController extends Controller {
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    /**
     * JWT authentication.
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
     *
     * @return JsonResponse
     */
    public function me() {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout() {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh token
     * Generates a new valid token for the next 3600 seconds
     * Inorder to generate the new token, a working (Bearer)token has to be provided in the header.
     *
     * @return JsonResponse
     */
    public function refresh() {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        return $this->respondWithToken($guard->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token) {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('api');

        return response()->json(
            [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60,
                'user' => auth('api')->user(),
            ]
        );
    }
}
