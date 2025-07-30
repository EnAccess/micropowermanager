<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminJWT {
    public function handle(Request $request, \Closure $next): Response {
        return $next($request);
    }
}
