<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\JWTGuard;

#[Group('AgentApp - Auth', 'Responsible for AgentAPP-API-Call authentications.', weight: 20)]
class AgentAuthController extends Controller {
    /**
     * Create a new AuthController instance.
     */
    public function __construct(
        private AgentService $agentService,
    ) {}

    /**
     * Get the JWT authentication guard.
     */
    protected function guard(): JWTGuard {
        /** @var JWTGuard $guard */
        $guard = auth()->guard('agent_api');

        return $guard;
    }

    /**
     * Agent login.
     *
     * Login a user of the Agent App and get JWT token via given credentials.
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
     * Get the authenticated Agent.
     *
     * @return JsonResponse
     */
    public function me() {
        $agent = auth('agent_api')->user();

        if (method_exists($agent, 'getRoleNames')) {
            /** @var Agent $agent */
            $roles = $agent->getRoleNames()->toArray();
        } else {
            $roles = [];
        }
        if (method_exists($agent, 'getAllPermissions')) {
            /** @var Agent $agent */
            $permissions = $agent->getAllPermissions()->pluck('name')->toArray();
        } else {
            $permissions = [];
        }

        return response()->json([
            /* @var Agent */
            'agent' => $agent,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Agent logout.
     *
     * Logout the AgentApp user and invalidate the JWT token.
     */
    public function logout(): JsonResponse {
        auth('agent_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh Agent token.
     *
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
        /** @var Agent $agent */
        $agent = $this->guard()->user();
        $roles = $agent->getRoleNames()->toArray();
        $permissions = $agent->getAllPermissions()->pluck('name')->toArray();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'agent' => $agent,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }
}
