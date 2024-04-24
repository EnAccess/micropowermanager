<?php

namespace Inensus\AirtelPaymentProvider\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Closure;
use SimpleXMLElement;

class AirtelTransactionAuthorizationMiddleware
{

    // https://developer.okta.com/blog/2019/02/04/create-and-verify-jwts-in-php
    public function handle(Request $request, Closure $next)
    {
        $jwt = $request->header('Authorization');
        $tokenParts = explode('.', $jwt);
        $payload = json_decode(base64_decode($tokenParts[1]), true);
        $expiration = Carbon::createFromTimestamp($payload['exp']);
        $tokenExpired = (Carbon::now()->diffInSeconds($expiration, false) < 0);

        $user = auth('api')->user();
        if (!$user ){
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>401</STATUS>' .
                '<MESSAGE>Invalid Token</MESSAGE>' .
                '</COMMAND>';

            return $xmlResponse;
        }
        if ($tokenExpired) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>401</STATUS>' .
                '<MESSAGE>Token has expired</MESSAGE>' .
                '</COMMAND>';

            echo $xmlResponse;
            return false;
        }

        return $next($request);
    }
}