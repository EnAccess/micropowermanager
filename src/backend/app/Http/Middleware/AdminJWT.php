<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class AdminJWT {
    public function handle(Request $request, \Closure $next): mixed {
        return $next($request);
    }
}
