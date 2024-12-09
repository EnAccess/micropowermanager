<?php

namespace App\Http\Middleware;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\UserNotDefinedException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class JwtMiddleware extends BaseMiddleware {
    /**
     * Handle an incoming request.
     *
     * @param Request  $request
     * @param \Closure $next
     * @param string   $type
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, string $type = 'user'): mixed {
        try {
            $id = JWTAuth::parseToken()->getPayload()->get('sub');
            if ($type === 'agent') {
                $user = Agent::query()->findOrFail($id);
            } elseif ($type === 'user') {
                $user = User::query()->findOrFail($id);
            } else {
                throw new UserNotDefinedException('Authentication failed');
            }
        } catch (\Exception $e) {
            if ($e instanceof ModelNotFoundException) {
                return $this->generateResponse('No user found for authentication');
            }
            if ($e instanceof UserNotDefinedException) {
                return $this->generateResponse($e->getMessage());
            }

            if ($e instanceof TokenInvalidException) {
                return $this->generateResponse('Token is Invalid');
            }

            if ($e instanceof TokenExpiredException) {
                return $this->generateResponse('Token is Expired');
            }

            return $this->generateResponse('Authorization Token not found');
        }
        $request->attributes->add(['user' => $user]);

        return $next($request);
    }

    private function generateResponse(string $message, $status = 400): JsonResponse {
        return response()->json(['data' => ['message' => $message, 'status' => $status]]);
    }
}
