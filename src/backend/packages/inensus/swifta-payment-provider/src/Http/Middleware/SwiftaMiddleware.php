<?php

namespace Inensus\SwiftaPaymentProvider\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class SwiftaMiddleware {
    public function handle(Request $request, \Closure $next) {
        if (auth()->payload()->get('usr') !== 'swifta-token') {
            $data = collect([
                'success' => 0,
                'message' => 'Authentication field.',
            ]);

            return new Response($data, 401);
        }
        try {
            $this->checkCipherIsValid($request);
        } catch (\Exception $exception) {
            $data = collect([
                'success' => 0,
                'message' => $exception->getMessage(),
            ]);

            return new Response($data, 400);
        }

        return $next($request);
    }

    private function checkCipherIsValid(Request $request) {
        $hash = md5('Inensus'.$request->input('timestamp').$request->input('amount').'Swifta');
        if ($request->input('cipher') != $hash) {
            Log::warning('Swifta Transaction Validation Failed', [
                'message' => 'Cipher validation field.',
            ]);
            throw new \Exception('cipher validation field.');
        }
    }
}
