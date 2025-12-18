<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;

class TelescopeBasicAuthMiddleware {
    /**
     * Handle an incoming request.
     *
     *
     * @return mixed
     */
    public function handle(Request $request, \Closure $next) {
        if (app()->environment('development')) {
            return $next($request);
        }

        $username = config('telescope.http_basic_auth.username');
        $password = config('telescope.http_basic_auth.password');

        if (empty($username) || empty($password)) {
            abort(403, 'Telescope access not configured.');
        }

        // Check HTTP Basic Auth
        if ($request->getUser() !== $username || $request->getPassword() !== $password) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Telescope"',
            ]);
        }

        return $next($request);
    }
}
