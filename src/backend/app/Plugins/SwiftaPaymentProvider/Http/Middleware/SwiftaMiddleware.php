<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Middleware;

use App\Plugins\SwiftaPaymentProvider\Exceptions\CipherNotValidException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\JWTGuard;

// Test

class SwiftaMiddleware {
    /**
     * @return Request|Response
     */
    public function handle(Request $request, \Closure $next) {
        /** @var JWTGuard $guard */
        $guard = auth();

        if ($guard->payload()->get('usr') !== 'swifta-token') {
            $data = collect([
                'success' => 0,
                'message' => 'Authentication field.',
            ]);

            return new Response($data, 401);
        }
        $this->checkCipherIsValid($request);

        return $next($request);
    }

    private function checkCipherIsValid(Request $request): void {
        $hash = md5('Inensus'.$request->input('timestamp').$request->input('amount').'Swifta');
        if ($request->input('cipher') != $hash) {
            Log::warning('Swifta Transaction Validation Failed', [
                'message' => 'Cipher validation field.',
            ]);
            throw new CipherNotValidException('cipher validation field.');
        }
    }
}
