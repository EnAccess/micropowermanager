<?php

namespace Inensus\AirtelPaymentProvider\Http\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Closure;
use Exception;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use SimpleXMLElement;

class AirtelTransactionAuthorizationMiddleware
{
    private $secretKey = 'KbPeShVmYq3t6w9z$C&F)J@NcQfTjWnZr4u7x!A%D*G-KaPdSgVkXp2s5v8y/B?E';

    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$this->isValidAuthHeader($authHeader)) {
            return $this->generateXmlResponse(401, 'Invalid Token');
        }

        $token = $this->extractToken($authHeader);

        try {
            $decoded = $this->decodeToken($token);
            $this->checkTokenExpiration($decoded);

            $payload = $decoded->payload ?? null;
            $txId = $payload->txnId ?? null;

            $transactionXml = new SimpleXMLElement($request->getContent());
            $transactionData = json_encode($transactionXml);
            $transactionData = json_decode($transactionData, true);

            if (isset($transactionData['REFERENCE1']) && $transactionData['REFERENCE1'] != $txId) {
                throw new Exception('Access token contains wrong reference1');
            }
            if (isset($transactionData['TXNID']) && $transactionData['TXNID'] != $txId) {
                throw new Exception('Access token contains wrong txnId');
            }

        } catch (Exception $e) {
            Log::error('Error while decoding JWT token: ' . $e->getMessage());
            return $this->generateXmlResponse(401, $e->getMessage());
        }

        return $next($request);
    }

    private function isValidAuthHeader($authHeader)
    {
        return $authHeader && str_starts_with($authHeader, 'Bearer ');
    }

    private function extractToken($authHeader)
    {
        return substr($authHeader, 7); // Remove 'Bearer ' from the beginning
    }

    private function decodeToken($token)
    {
        return JWT::decode($token, new Key($this->secretKey, 'HS512'));
    }

    private function checkTokenExpiration($decoded)
    {
        $currentTime = Carbon::now()->timestamp;
        if ($decoded->exp < $currentTime) {
            echo $this->generateXmlResponse(401, 'Token has expired');
            exit;
        }
    }

    private function generateXmlResponse($status, $message)
    {
        return response()->make(
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<COMMAND>' .
            '<STATUS>' . $status . '</STATUS>' .
            '<MESSAGE>' . $message . '</MESSAGE>' .
            '</COMMAND>',
            200,
            ['Content-Type' => 'application/xml']
        );
    }
}
