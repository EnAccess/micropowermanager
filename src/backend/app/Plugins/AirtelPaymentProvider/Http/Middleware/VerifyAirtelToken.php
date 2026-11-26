<?php

namespace App\Plugins\AirtelPaymentProvider\Http\Middleware;

use App\Plugins\AirtelPaymentProvider\Exceptions\AirtelRequestException;
use App\Plugins\AirtelPaymentProvider\Services\AirtelCredentialService;
use App\Plugins\AirtelPaymentProvider\Services\AirtelTokenVerifier;
use App\Plugins\AirtelPaymentProvider\Support\AirtelXml;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAirtelToken {
    public const string COMMAND_ATTRIBUTE = 'airtelCommand';

    public function __construct(
        private AirtelCredentialService $credentialService,
        private AirtelTokenVerifier $tokenVerifier,
    ) {}

    public function handle(Request $request, \Closure $next): Response {
        $token = $request->bearerToken();
        $secret = $this->credentialService->getCredentials()->jwt_secret;

        if ($token === null || $secret === null || $secret === '') {
            return AirtelXml::response(401, 'Invalid Token');
        }

        try {
            $tokenTransactionId = $this->tokenVerifier->verify($token, $secret);
        } catch (AirtelRequestException $exception) {
            return AirtelXml::response(401, $exception->getMessage());
        }

        try {
            $command = AirtelXml::parse($request->getContent());
        } catch (AirtelRequestException $exception) {
            return AirtelXml::response(400, $exception->getMessage());
        }

        if (isset($command['REFERENCE1']) && $command['REFERENCE1'] !== $tokenTransactionId) {
            return AirtelXml::response(400, 'Access token contains wrong reference1');
        }
        if (isset($command['TXNID']) && $command['TXNID'] !== $tokenTransactionId) {
            return AirtelXml::response(400, 'Access token contains wrong txnId');
        }

        $request->attributes->set(self::COMMAND_ATTRIBUTE, $command);

        return $next($request);
    }
}
