<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Exceptions\PesapalApiException;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\GetTransactionStatusResource;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\RegisterIpnResource;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\SubmitOrderRequestResource;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTokenService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class PesapalApiService {
    public function __construct(
        private PesapalApi $api,
        private PesapalCredentialService $credentialService,
        private PesapalTokenService $tokenService,
    ) {}

    /**
     * @return array{ipn_id: string|null, error: string|null}
     */
    public function registerIpn(PesapalCredential $credential, string $ipnUrl): array {
        try {
            $token = $this->tokenService->getToken($credential);
            $resource = new RegisterIpnResource($this->getBaseUrl($credential), $token, $ipnUrl);
            /** @var RegisterIpnResource $response */
            $response = $this->api->doRequest($resource);

            $ipnId = $response->getIpnId();
            if (empty($ipnId)) {
                return [
                    'ipn_id' => null,
                    'error' => $response->getError() ?? 'PesaPal RegisterIPN returned no ipn_id',
                ];
            }

            return ['ipn_id' => $ipnId, 'error' => null];
        } catch (GuzzleException|PesapalApiException|\RuntimeException $exception) {
            Log::error('Pesapal RegisterIPN failed', [
                'exception_message' => $exception->getMessage(),
                'ipn_url' => $ipnUrl,
            ]);

            return ['ipn_id' => null, 'error' => $exception->getMessage()];
        }
    }

    /**
     * @return array{redirect_url: ?string, order_tracking_id: ?string, merchant_reference: ?string, error: ?string}
     */
    public function submitOrder(PesapalTransaction $transaction, ?string $payerPhoneNumber = null): array {
        $credential = $this->credentialService->getCredentials();

        try {
            $token = $this->tokenService->getToken($credential);
            $callbackUrl = $this->resolveCallbackUrl($credential, $transaction);
            $resource = new SubmitOrderRequestResource(
                $credential,
                $transaction,
                $this->getBaseUrl($credential),
                $token,
                $callbackUrl,
                $payerPhoneNumber,
            );

            /** @var SubmitOrderRequestResource $response */
            $response = $this->api->doRequest($resource);

            $error = $response->getError();
            $orderTrackingId = $response->getOrderTrackingId();
            $merchantReference = $response->getMerchantReference();
            $redirectUrl = $response->getRedirectUrl();

            if ($error !== null || empty($orderTrackingId) || empty($redirectUrl)) {
                return [
                    'redirect_url' => null,
                    'order_tracking_id' => null,
                    'merchant_reference' => null,
                    'error' => $error ?? 'PesaPal SubmitOrderRequest returned no redirect URL',
                ];
            }

            $transaction->setOrderTrackingId($orderTrackingId);
            if (!empty($merchantReference)) {
                $transaction->setMerchantReference($merchantReference);
            }
            $transaction->setPaymentUrl($redirectUrl);
            $transaction->save();

            Log::info('Pesapal SubmitOrderRequest success', [
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'transaction_reference' => $transaction->getReferenceId(),
            ]);

            return [
                'redirect_url' => $redirectUrl,
                'order_tracking_id' => $orderTrackingId,
                'merchant_reference' => $merchantReference,
                'error' => null,
            ];
        } catch (GuzzleException|PesapalApiException|\RuntimeException|\InvalidArgumentException $exception) {
            Log::error('Pesapal SubmitOrderRequest exception', [
                'exception_message' => $exception->getMessage(),
                'transaction_reference' => $transaction->getReferenceId(),
            ]);

            $transaction->setStatus(PesapalTransaction::STATUS_FAILED);
            $transaction->save();

            return [
                'redirect_url' => null,
                'order_tracking_id' => null,
                'merchant_reference' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array{status_code: ?int, status_description: string, amount: float, currency: string, payment_method: string, confirmation_code: string, merchant_reference: string, error: ?string}
     */
    public function getTransactionStatus(string $orderTrackingId): array {
        $credential = $this->credentialService->getCredentials();

        try {
            $token = $this->tokenService->getToken($credential);
            $resource = new GetTransactionStatusResource($this->getBaseUrl($credential), $token, $orderTrackingId);

            /** @var GetTransactionStatusResource $response */
            $response = $this->api->doRequest($resource);

            $error = $response->getError();
            if ($error !== null) {
                return $this->emptyStatusResult($error);
            }

            return [
                'status_code' => $response->getStatusCode(),
                'status_description' => $response->getStatusDescription(),
                'amount' => $response->getAmount(),
                'currency' => $response->getCurrency(),
                'payment_method' => $response->getPaymentMethod(),
                'confirmation_code' => $response->getConfirmationCode(),
                'merchant_reference' => $response->getMerchantReference(),
                'error' => null,
            ];
        } catch (GuzzleException|PesapalApiException|\RuntimeException $exception) {
            Log::error('Pesapal GetTransactionStatus exception', [
                'exception_message' => $exception->getMessage(),
                'order_tracking_id' => $orderTrackingId,
            ]);

            return $this->emptyStatusResult($exception->getMessage());
        }
    }

    private function resolveCallbackUrl(PesapalCredential $credential, PesapalTransaction $transaction): string {
        $callback = $credential->getCallbackUrl();
        if (in_array($callback, [null, '', '0'], true)) {
            throw new \InvalidArgumentException('PesaPal callback URL is not configured; save credentials to populate it.');
        }
        // Reference is appended so the customer's browser returns to a deep-linked result page.
        $separator = str_contains($callback, '?') ? '&' : '?';

        return $callback.$separator.'reference='.urlencode($transaction->getReferenceId());
    }

    private function getBaseUrl(PesapalCredential $credential): string {
        $key = $credential->isLive()
            ? 'pesapal-payment-provider.pesapal_api_url_live'
            : 'pesapal-payment-provider.pesapal_api_url_test';

        return (string) config($key);
    }

    /**
     * @return array{status_code: null, status_description: string, amount: float, currency: string, payment_method: string, confirmation_code: string, merchant_reference: string, error: string}
     */
    private function emptyStatusResult(string $error): array {
        return [
            'status_code' => null,
            'status_description' => '',
            'amount' => 0.0,
            'currency' => '',
            'payment_method' => '',
            'confirmation_code' => '',
            'merchant_reference' => '',
            'error' => $error,
        ];
    }
}
