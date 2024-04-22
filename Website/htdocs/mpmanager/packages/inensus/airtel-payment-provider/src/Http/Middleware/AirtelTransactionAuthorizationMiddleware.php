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

        $transactionXml = new SimpleXMLElement($request->getContent());
        $transactionData = json_encode($transactionXml);
        $transactionData = json_decode($transactionData, true);

        $transId = null;

        if (isset($transactionData['TXNID'])) {
            $transId = $transactionData['TXNID'];
        }

        if (isset($transactionData['REFERENCE1'])) {
            $transId = $transactionData['REFERENCE1'];
        }


        if (!$transId || $transId !== $payload['Payload']['txnId']) {
            $xmlResponse =
                '<?xml version="1.0" encoding="UTF-8"?>' .
                '<COMMAND>' .
                '<STATUS>401</STATUS>' .
                '<MESSAGE>Invalid Token</MESSAGE>' .
                '</COMMAND>';


            return $xmlResponse;
        }
        return $next($request);
    }
}