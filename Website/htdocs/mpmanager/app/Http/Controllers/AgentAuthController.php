<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

/**
 * @group   Agent-Authenticator
 * Class AgentAuthController
 * Responsible for AgentAPP-API-Call authentications.
 * @package App\Http\Controllers
 */
class AgentAuthController extends Controller
{

    private $agentService;

    /**
     * Create a new AuthController instance.
     *
     * @param AgentService $agentService
     */
    public function __construct(AgentService $agentService)
    {
        $this->agentService = $agentService;
        $this->middleware('auth:agent_api', ['except' => ['login']]);
    }

    /**
     * Get JWT via given credentials.
     *
     * @bodyParam email string required
     * @bodyParam password string required
     * @return    JsonResponse
     */
    public function login()
    {

        $credentials = request(['email', 'password']);
        if (!$token = auth('agent_api')->setTTL(525600)->attempt($credentials)) {
            return response()->json(['data' => ['message' => 'Unauthorized', 'status' => 401]], 401);
        }
        $agent = Agent::find(auth('agent_api')->user()->id);
        $deviceId = request()->header('device-id');

        $this->agentService->updateDevice($agent, $deviceId);
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me()
    {
        return response()->json(auth('agent_api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth('agent_api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     * A valid JWT token has to be sent to refresh the token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('agent_api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json(
            [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('agent_api')->factory()->getTTL() * 60,
            'agent' => auth('agent_api')->user(),
            ]
        );
    }
}
