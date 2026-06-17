<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Clients;

use App\Plugins\VodacomMzPaymentProvider\Exceptions\VodacomMzApiResponseException;
use App\Plugins\VodacomMzPaymentProvider\Models\VodacomMzCredential;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzCredentialService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

/**
 * Client for Vodacom Mozambique's M-Pesa OpenAPI, the API MPM uses to initiate transactions.
 * Each public method maps to one OpenAPI endpoint: C2B Payment and Query Transaction Status.
 */
class VodacomMzApiClient {
    /** The OpenAPI returns this in `output_ResponseCode` when a request is accepted. */
    public const RESPONSE_SUCCESS = 'INS-0';

    /**
     * The C2B Payment endpoint is single-stage and synchronous: the OpenAPI holds the connection
     * open while the payer is prompted for their M-Pesa PIN and the payment is processed, only then
     * responding. The request timeout must cover that whole interaction, so it is far longer than
     * for other endpoints.
     */
    private const int C2B_TIMEOUT_SECONDS = 120;

    private const int DEFAULT_TIMEOUT_SECONDS = 30;

    private const int CONNECT_TIMEOUT_SECONDS = 30;

    public function __construct(
        private Client $httpClient,
        private VodacomMzCredentialService $credentialService,
    ) {}

    /**
     * C2B Payment endpoint: single-stage customer-to-business push that prompts the payer for their
     * M-Pesa PIN.
     *
     * @param string $transactionReference the reference of the transaction for the customer or
     *                                     business making the transaction, e.g. a smartcard number
     *                                     for a TV subscription or a utility bill reference number
     * @param string $customerMsisdn       the payer's phone number in E.164 format, e.g. "+258848495010";
     *                                     it is converted to the bare MSISDN the OpenAPI expects here
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
            'input_CustomerMSISDN' => $this->toMsisdn($customerMsisdn),
            'input_Amount' => $amount,
            'input_ThirdPartyReference' => $thirdPartyReference,
            'input_ServiceProviderCode' => $credential->getServiceProviderCode(),
        ], self::C2B_TIMEOUT_SECONDS);
    }

    /**
     * The OpenAPI expects the MSISDN as bare international digits with no leading "+", e.g.
     * "258848495010". Everywhere else in MPM phone numbers are kept in E.164 ("+258848495010"); this
     * is the single point where that canonical form is adapted to what Vodacom requires.
     */
    private function toMsisdn(string $e164PhoneNumber): string {
        return ltrim(phone($e164PhoneNumber)->formatE164(), '+');
    }

    /**
     * Query Transaction Status endpoint: fetches the current state of a previously initiated C2B
     * payment, used to reconcile transactions left unconfirmed by the synchronous push.
     *
     * @return array<string, mixed>
     */
    public function queryTransactionStatus(string $queryReference, string $thirdPartyReference): array {
        $credential = $this->credentialService->getCredentials();

        return $this->send($credential, 'GET', 18353, '/ipg/v1x/queryTransactionStatus/', [
            'input_QueryReference' => $queryReference,
            'input_ThirdPartyReference' => $thirdPartyReference,
            'input_ServiceProviderCode' => $credential->getServiceProviderCode(),
        ]);
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function send(VodacomMzCredential $credential, string $method, int $port, string $path, array $params, int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS): array {
        $url = $credential->buildUri($port, $path);
        $payloadKey = $method === 'GET' ? 'query' : 'json';

        try {
            // The OpenAPI returns business failures (e.g. INS-2006 insufficient balance) as a 4xx
            // with the detail in the body, so http_errors stays off and the decoded body
            // is returned as-is for the caller to inspect via `output_ResponseCode`.
            $response = $this->httpClient->request($method, $url, [
                $payloadKey => $params,
                'headers' => $this->headers($credential),
                'http_errors' => false,
                'timeout' => $timeoutSeconds,
                'connect_timeout' => self::CONNECT_TIMEOUT_SECONDS,
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
