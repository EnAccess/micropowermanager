<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class CorsMiddleware
{
    public function handle(Request $request, \Closure $next)
    {
        $accessControlRequestMethod = $request->header('access-control-request-method');
        $accessControlRequestHeaders = $request->header('access-control-request-headers');

        if ($request->getMethod() === "OPTIONS") {
            return response('')->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Max-Age', 0)
                ->header('Access-Control-Allow-Methods', $accessControlRequestMethod)
                ->header('Access-Control-Allow-Headers', $accessControlRequestHeaders);
        }

        $response = $next($request);
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Max-Age', 0);

        if ($accessControlRequestMethod) {
            $response->header('Access-Control-Allow-Methods', $accessControlRequestMethod);
        }
        if ($accessControlRequestHeaders) {
            $response->header('Access-Control-Allow-Headers', $accessControlRequestHeaders);
        }
        return $response;
    }
}
