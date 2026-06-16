<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Clients;

use App\Plugins\VodacomMzPaymentProvider\Exceptions\VodacomMzApiResponseException;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzCredential;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzCredentialService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class VodacomMzApiClient {
    /** IPG returns this in `output_ResponseCode` when a request is accepted. */
    public const RESPONSE_SUCCESS = 'INS-0';

    public function __construct(
        private Client $httpClient,
        private VodacomMzCredentialService $credentialService,
    ) {}

    /**
     * Customer-to-business single-stage payment (pushes a PIN prompt to the payer).
     *
     * @param string $transactionReference the reference of the transaction for the customer or
     *                                     business making the transaction, e.g. a smartcard number
     *                                     for a TV subscription or a utility bill reference number
     * @param string $customerMsisdn       the payer's phone number in international format, e.g. "258848495010"
     * @param float  $amount               the amount to charge the payer
     * @param string $thirdPartyReference  the unique reference of the third party system; used to
     *                                     track the transaction when querying its status
     *
     * @return array<string, mixed>
     */
    public function c2bPayment(
        string $transactionReference,
        string $customerMsisdn,
        float $amount,
        string $thirdPartyReference,
    ): array {
        $credential = $this->credentialService->getCredentials();

        return $this->send($credential, 'POST', 18352, '/ipg/v1x/c2bPayment/singleStage/', [
            'input_TransactionReference' => $transactionReference,
            'input_CustomerMSISDN' => $customerMsisdn,
            'input_Amount' => (string) $amount,
            'input_ThirdPartyReference' => $thirdPartyReference,
            'input_ServiceProviderCode' => $credential->service_provider_code,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryTransactionStatus(string $queryReference, string $thirdPartyReference): array {
        $credential = $this->credentialService->getCredentials();

        return $this->send($credential, 'GET', 18353, '/ipg/v1x/queryTransactionStatus/', [
            'input_QueryReference' => $queryReference,
            'input_ThirdPartyReference' => $thirdPartyReference,
            'input_ServiceProviderCode' => $credential->service_provider_code,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function send(VodacomMzCredential $credential, string $method, int $port, string $path, array $params): array {
        $url = $credential->buildUri($port, $path);
        $payloadKey = $method === 'GET' ? 'query' : 'json';

        try {
            // IPG returns business failures (e.g. INS-2006 insufficient balance) as a 4xx
            // with the detail in the body, so http_errors stays off and the decoded body
            // is returned as-is for the caller to inspect via `output_ResponseCode`.
            $response = $this->httpClient->request($method, $url, [
                $payloadKey => $params,
                'headers' => $this->headers($credential),
                'http_errors' => false,
            ]);
        } catch (GuzzleException $exception) {
            Log::critical('Vodacom MZ API request failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);
            throw new VodacomMzApiResponseException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return json_decode((string) $response->getBody(), true) ?? [];
    }

    /**
     * @return array<string, string>
     */
    private function headers(VodacomMzCredential $credential): array {
        return [
            'Authorization' => 'Bearer '.$credential->getBearerToken(),
            'Origin' => '*',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
