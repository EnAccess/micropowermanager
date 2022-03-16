<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Claims\Collection;

class AdminJWT
{
    /**
     * Handle an incoming request.
     *
     * @param  Request $request
     * @param  Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        /**
         * @var Response
         */
        $response = $next($request);

        return $response;
    }

    private function createJWT()
    {
        $payload = JWTFactory::sub(123)->exp(time() + 3600)->aud('foo')->foo(['bar' => 'baz'])->make();
        $token = JWTAuth::encode($payload);
        return $token;
    }

    private function validateJWT(): bool
    {
        try {
            $token = JWTAuth::parseToken();
        } catch (Exception $e) {
            return false;
        } finally {
            return true;
        }
    }
}
