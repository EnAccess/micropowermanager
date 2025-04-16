<?php

namespace App\Http\Controllers;

use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

/**
 * @group   Agent-Authenticator
 * Class AgentAuthController
 * Responsible for AgentAPP-API-Call authentications.
 */
class AgentAuthController extends Controller {
    /**
     * Create a new AuthController instance.
     *
     * @param AgentService $agentService
     */
    public function __construct(private AgentService $agentService) {
        $this->middleware('auth:agent_api', ['except' => ['login']]);
    }

    /**
     * Get the JWT authentication guard.
     *
     * @return JWTGuard
     */
    protected function guard(): JWTGuard {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('agent_api');

        return $guard;
    }

    /**
     * Get JWT via given credentials.
     *
     * @bodyParam email string required
     * @bodyParam password string required
     *
     * @return JsonResponse
     */
    public function login(Request $request) {
        $credentials = $request->only(['email', 'password']);

        if (!$token = $this->guard()->setTTL(525600)->attempt($credentials)) {
            return response()->json(['data' => ['message' => 'Unauthorized', 'status' => 401]], 401);
        }

        // if the Agent app sends us a agent-device-id in the header
        // we update the agent in db
        $deviceId = $request->header('device-id');
        if ($deviceId) {
            $agentId = $this->guard()->user()->id;
            $agent = $this->agentService->getById($agentId);
            $this->agentService->updateDevice($agent, $deviceId);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me() {
        return response()->json(auth('agent_api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout() {
        auth('agent_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     * A valid JWT token has to be sent to refresh the token.
     *
     * @return JsonResponse
     */
    public function refresh() {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token) {
        return response()->json(
            [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $this->guard()->factory()->getTTL() * 60,
                'agent' => $this->guard()->user(),
            ]
        );
    }
}
