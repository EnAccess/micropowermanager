<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomMobileMoney\Services;

use App\Jobs\ProcessPayment;
use App\Models\Address\Address;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Models\Transaction\Transaction;
use App\Plugins\SafaricomMobileMoney\Models\SafaricomCredential;
use App\Plugins\SafaricomMobileMoney\Models\SafaricomTransaction;
use App\Services\AbstractPaymentAggregatorTransactionService;
use App\Services\DeviceService;
use App\Services\Interfaces\IBaseService;
use App\Services\Interfaces\PaymentInitiator;
use App\Services\PersonService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ramsey\Uuid\Uuid;

/**
 * @extends AbstractPaymentAggregatorTransactionService<SafaricomTransaction>
 *
 * @implements IBaseService<SafaricomTransaction>
 */
class SafaricomTransactionService extends AbstractPaymentAggregatorTransactionService implements IBaseService, PaymentInitiator {
    public function __construct(
        private Meter $meter,
        private Address $address,
        private Transaction $transaction,
        private SafaricomTransaction $safaricomTransaction,
        private SafaricomCredentialService $credentialService,
        private SafaricomAuthService $authService,
    ) {
        parent::__construct(
            $this->meter,
            $this->address,
            $this->transaction,
            $this->safaricomTransaction,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function initializeTransactionData(): array {
        return [
            'order_id' => Uuid::uuid4()->toString(),
            'reference_id' => Uuid::uuid4()->toString(),
            'serial_id' => $this->getSerialId(),
            'status' => SafaricomTransaction::STATUS_REQUESTED,
            'currency' => 'KES',
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'metadata' => [
                'serial_id' => $this->getSerialId(),
                'customer_id' => $this->customerId,
            ],
        ];
    }

    public function getByOrderId(string $orderId): ?SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->where('order_id', '=', $orderId)->first();
    }

    public function getByReferenceId(string $referenceId): ?SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->where('reference_id', '=', $referenceId)->first();
    }

    public function getByCheckoutRequestId(string $checkoutRequestId): ?SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->where('checkout_request_id', '=', $checkoutRequestId)->first();
    }

    public function getByMpesaReceipt(string $receipt): ?SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->where('mpesa_receipt_number', '=', $receipt)->first();
    }

    /**
     * @return Collection<int, SafaricomTransaction>
     */
    public function getByStatus(int $status): Collection {
        return $this->safaricomTransaction->newQuery()->where('status', '=', $status)->get();
    }

    public function getById(int $id): ?SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->find($id);
    }

    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->safaricomTransaction->newQuery();
        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($safaricomTransaction, array $data): SafaricomTransaction {
        $safaricomTransaction->update($data);
        $safaricomTransaction->fresh();

        return $safaricomTransaction;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): SafaricomTransaction {
        return $this->safaricomTransaction->newQuery()->create($data);
    }

    public function delete($safaricomTransaction): ?bool {
        return $safaricomTransaction->delete();
    }

    public function getSafaricomTransaction(): SafaricomTransaction {
        /** @var SafaricomTransaction $tx */
        $tx = $this->paymentProviderTransaction;

        return $tx;
    }

    public function getSerialId(): ?string {
        return $this->meterSerialNumber;
    }

    public function processSuccessfulPayment(int $companyId, SafaricomTransaction $transaction): void {
        $id = $transaction->transaction->id;
        dispatch(new ProcessPayment($companyId, $id));
        $transaction->setStatus(SafaricomTransaction::STATUS_SUCCESS);
        $transaction->save();
    }

    public function processFailedPayment(SafaricomTransaction $transaction): void {
        $transaction->setStatus(SafaricomTransaction::STATUS_FAILED);
        $transaction->save();
    }

    /**
     * Initiate an STK Push for `$sender` (the customer's phone number).
     * Creates a SafaricomTransaction + core Transaction in one DB transaction,
     * then issues the STK Push so we don't end up with orphaned transaction
     * rows when Daraja errors out synchronously.
     *
     * process_immediately is always false: M-PESA only knows the customer
     * paid once their async STK callback fires (or our poll picks it up).
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiatePayment(
        float $amount,
        string $sender,
        string $message,
        string $type,
        int $customerId,
        ?string $serialId = null,
    ): array {
        $deviceType = null;
        if ($serialId !== null) {
            $device = resolve(DeviceService::class)->getBySerialNumber($serialId);
            $deviceType = $device?->device_type;
        }

        $credential = $this->credentialService->getCredentials();

        $normalisedPhone = $this->normalisePhoneNumber($sender);
        if ($normalisedPhone === null) {
            throw new \InvalidArgumentException('Phone number must be a Kenyan M-PESA number (e.g. 0712345678 or 254712345678).');
        }

        // Daraja caps AccountReference to 12 chars and TransactionDesc to 13.
        // The customer sees AccountReference in the STK PIN prompt, so prefer
        // the meter serial; fall back to the short prefix of our reference_id.
        $referenceId = Uuid::uuid4()->toString();
        $accountReference = $this->truncate($serialId ?: substr($referenceId, 0, 8), 12);
        $transactionDesc = $this->truncate($message ?: 'MPM Payment', 13);

        try {
            DB::connection('tenant')->beginTransaction();

            /** @var SafaricomTransaction $safaricomTxn */
            $safaricomTxn = $this->safaricomTransaction->newQuery()->create([
                'amount' => $amount,
                'currency' => 'KES',
                'order_id' => Uuid::uuid4()->toString(),
                'reference_id' => $referenceId,
                'status' => SafaricomTransaction::STATUS_REQUESTED,
                'customer_id' => $customerId,
                'serial_id' => $serialId,
                'device_type' => $deviceType,
                'phone_number' => $normalisedPhone,
                'account_reference' => $accountReference,
                'transaction_desc' => $transactionDesc,
                'metadata' => [
                    'customer_id' => $customerId,
                    'serial_id' => $serialId,
                    'transaction_type' => $type,
                ],
            ]);

            /** @var Transaction $transaction */
            $transaction = $safaricomTxn->transaction()->create([
                'amount' => $amount,
                'sender' => $normalisedPhone,
                'message' => $message,
                'type' => $type,
            ]);

            $result = $this->sendStkPush($safaricomTxn, $credential);
            if ($result['error'] !== null) {
                throw new \RuntimeException('Safaricom STK Push failed: '.$result['error']);
            }

            $safaricomTxn->setCheckoutRequestId((string) $result['checkout_request_id']);
            $safaricomTxn->setMerchantRequestId((string) $result['merchant_request_id']);
            $safaricomTxn->update(['response_data' => $result['raw']]);

            DB::connection('tenant')->commit();

            return [
                'transaction' => $transaction,
                'provider_data' => [
                    'reference_id' => $safaricomTxn->getReferenceId(),
                    'checkout_request_id' => $safaricomTxn->getCheckoutRequestId(),
                    'merchant_request_id' => $safaricomTxn->getMerchantRequestId(),
                    'customer_message' => $result['customer_message'] ?? null,
                ],
                'process_immediately' => false,
            ];
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    /**
     * Apply a Daraja result code to a transaction. Drives both the STK Push
     * result webhook and the STK Push Query polling path so failed/cancelled
     * payments don't leave a transaction stuck in REQUESTED. Server-side
     * authoritative — we never trust the inbound payload's status fields
     * directly.
     *
     * Daraja result-code semantics (https://developer.safaricom.co.ke):
     *   0    = success
     *   1    = insufficient funds
     *   1001 = subscriber lock (another tx in progress)
     *   1019 = transaction expired
     *   1025 = push request error
     *   1032 = user cancelled
     *   1037 = phone unreachable / DS timeout
     *   2001 = invalid PIN
     *   9999 = general error
     *
     * @param array<string, mixed> $payload
     */
    public function applyResultCode(SafaricomTransaction $transaction, int $resultCode, array $payload, int $companyId): void {
        if (!empty($payload['mpesa_receipt'])) {
            $transaction->setMpesaReceiptNumber((string) $payload['mpesa_receipt']);
            $transaction->setExternalTransactionId((string) $payload['mpesa_receipt']);
        }
        if (!empty($payload['transaction_date'])) {
            $transaction->transaction_date = $payload['transaction_date'];
        }
        $transaction->response_data = array_merge(
            $transaction->response_data ?? [],
            $payload,
            ['final_result_code' => $resultCode],
        );
        $transaction->save();

        // 1032 = user cancelled is conceptually "abandoned", not a hard
        // failure. Everything else non-zero is a failure.
        match (true) {
            $resultCode === 0 => $this->processSuccessfulPayment($companyId, $transaction),
            $resultCode === 1032 => $this->markAbandoned($transaction),
            default => $this->processFailedPayment($transaction),
        };
    }

    private function markAbandoned(SafaricomTransaction $transaction): void {
        $transaction->setStatus(SafaricomTransaction::STATUS_ABANDONED);
        $transaction->save();
    }

    /**
     * Poll Daraja's STK Push Query endpoint for the current status of a
     * pending transaction. Callbacks can be delayed or missed; this is the
     * source of truth the operator-facing payment page polls while showing
     * "Check your phone".
     *
     * Returns the merged status payload (same shape as the callback path
     * would have produced). When Daraja reports a terminal result code we
     * apply it through applyResultCode so the transaction state and the
     * polled view stay consistent.
     *
     * @return array{
     *   resolved: bool,
     *   result_code: ?int,
     *   result_desc: ?string,
     *   transaction_status: int,
     *   mpesa_receipt: ?string,
     *   error: ?string,
     * }
     */
    public function queryStatus(SafaricomTransaction $transaction, int $companyId): array {
        $checkoutRequestId = $transaction->getCheckoutRequestId();
        if ($checkoutRequestId === null || $checkoutRequestId === '') {
            return $this->statusSnapshot($transaction, null, 'Missing CheckoutRequestID');
        }

        // If we already concluded the transaction (via webhook or a previous
        // query), short-circuit — Daraja's query will eventually return
        // "transaction not found" once it ages out.
        if (in_array($transaction->getStatus(), [
            SafaricomTransaction::STATUS_SUCCESS,
            SafaricomTransaction::STATUS_COMPLETED,
            SafaricomTransaction::STATUS_FAILED,
            SafaricomTransaction::STATUS_ABANDONED,
        ], true)) {
            return $this->statusSnapshot($transaction);
        }

        $credential = $this->credentialService->getCredentials();
        $shortcode = $credential->getEffectiveShortcode();
        $passkey = $credential->getEffectivePasskey();
        if ($shortcode === '' || $passkey === '') {
            return $this->statusSnapshot($transaction, null, 'Shortcode/passkey not configured');
        }

        $timestamp = date('YmdHis');
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => base64_encode($shortcode.$passkey.$timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->authService->getAccessToken($credential),
                'Content-Type' => 'application/json',
            ])->post($this->stkPushQueryUrl($credential), $payload);
        } catch (\Throwable $e) {
            Log::warning('Safaricom STK Push Query exception', [
                'checkout_request_id' => $checkoutRequestId,
                'error' => $e->getMessage(),
            ]);

            return $this->statusSnapshot($transaction, null, $e->getMessage());
        }

        $body = $response->json();
        if (!is_array($body)) {
            return $this->statusSnapshot(
                $transaction,
                null,
                'Daraja returned non-JSON for STK Push Query: '.$response->body(),
            );
        }

        // Daraja returns one of three shapes:
        //   - Pending (no terminal outcome yet): errorCode/errorMessage like
        //     "500.001.1001 — The transaction is being processed".
        //   - Resolved: ResponseCode/ResultCode/ResultDesc all populated.
        //   - Not found (after TTL): different errorMessage.
        if (!isset($body['ResultCode'])) {
            $errorMessage = $body['errorMessage'] ?? null;
            $isStillProcessing = is_string($errorMessage)
                && (str_contains($errorMessage, 'being processed')
                    || ($body['errorCode'] ?? '') === '500.001.1001');
            if ($isStillProcessing) {
                return $this->statusSnapshot($transaction);
            }

            return $this->statusSnapshot($transaction, null, $errorMessage);
        }

        $resultCode = (int) $body['ResultCode'];

        if ($resultCode === 4999) {
            return $this->statusSnapshot($transaction);
        }

        $this->applyResultCode($transaction, $resultCode, [
            'source' => 'query',
            'result_code' => $resultCode,
            'result_desc' => $body['ResultDesc'] ?? null,
            'raw_query_response' => $body,
        ], $companyId);
        $transaction->refresh();

        return $this->statusSnapshot($transaction, $resultCode);
    }

    /**
     * @return array{
     *   resolved: bool,
     *   result_code: ?int,
     *   result_desc: ?string,
     *   transaction_status: int,
     *   mpesa_receipt: ?string,
     *   error: ?string,
     * }
     */
    private function statusSnapshot(SafaricomTransaction $transaction, ?int $resultCode = null, ?string $error = null): array {
        $status = $transaction->getStatus();
        $resolved = in_array($status, [
            SafaricomTransaction::STATUS_SUCCESS,
            SafaricomTransaction::STATUS_COMPLETED,
            SafaricomTransaction::STATUS_FAILED,
            SafaricomTransaction::STATUS_ABANDONED,
        ], true);
        $responseData = $transaction->response_data ?? [];
        $resolvedResultCode = $resultCode
            ?? (isset($responseData['final_result_code']) ? (int) $responseData['final_result_code'] : null);

        return [
            'resolved' => $resolved,
            'result_code' => $resolvedResultCode,
            'result_desc' => $responseData['result_desc'] ?? null,
            'transaction_status' => $status,
            'mpesa_receipt' => $transaction->getMpesaReceiptNumber(),
            'error' => $error,
        ];
    }

    /**
     * @return array{checkout_request_id: ?string, merchant_request_id: ?string, customer_message: ?string, raw: array<string, mixed>, error: ?string}
     */
    private function sendStkPush(SafaricomTransaction $transaction, SafaricomCredential $credential): array {
        $timestamp = date('YmdHis');
        $shortcode = $credential->getEffectiveShortcode();
        $passkey = $credential->getEffectivePasskey();

        if ($shortcode === '' || $passkey === '') {
            return [
                'checkout_request_id' => null,
                'merchant_request_id' => null,
                'customer_message' => null,
                'raw' => [],
                'error' => 'Shortcode and passkey are required for production. Save them on the credentials page.',
            ];
        }

        $password = base64_encode($shortcode.$passkey.$timestamp);

        $callbackUrl = $credential->result_url ?: $this->buildDefaultResultUrl();

        // account_reference + transaction_desc were already truncated to the
        // Daraja-enforced 12/13 char limits during transaction creation.
        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => (int) round($transaction->getAmount()),
            'PartyA' => $transaction->getPhoneNumber(),
            'PartyB' => $shortcode,
            'PhoneNumber' => $transaction->getPhoneNumber(),
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $transaction->account_reference ?: substr($transaction->getReferenceId(), 0, 8),
            'TransactionDesc' => $transaction->transaction_desc ?: 'MPM Payment',
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->authService->getAccessToken($credential),
                'Content-Type' => 'application/json',
            ])->post($this->stkPushUrl($credential), $payload);
        } catch (\Throwable $e) {
            Log::error('Safaricom STK Push exception', [
                'reference_id' => $transaction->getReferenceId(),
                'shortcode' => $shortcode,
                'phone' => $transaction->getPhoneNumber(),
                'error' => $e->getMessage(),
            ]);

            return [
                'checkout_request_id' => null,
                'merchant_request_id' => null,
                'customer_message' => null,
                'raw' => [],
                'error' => $e->getMessage(),
            ];
        }

        $body = $response->json();
        if (!$response->successful() || !is_array($body)) {
            $bodyExcerpt = substr($response->body(), 0, 500);
            Log::error('Safaricom STK Push rejected by Daraja', [
                'reference_id' => $transaction->getReferenceId(),
                'shortcode' => $shortcode,
                'status' => $response->status(),
                'body_excerpt' => $bodyExcerpt,
            ]);

            // Same "no HTML in the toast" rule as the OAuth path.
            $status = $response->status();
            $darajaErrorMessage = is_array($body) ? ($body['errorMessage'] ?? null) : null;
            $reason = match (true) {
                $darajaErrorMessage !== null => 'Daraja: '.$darajaErrorMessage,
                $status === 400 => 'Daraja rejected the STK Push payload (HTTP 400). Most often this is an unregistered BusinessShortCode for the environment.',
                $status === 401 => 'Daraja rejected the access token. Re-save credentials.',
                $status === 404 => 'Daraja said the STK Push endpoint was not found (HTTP 404).',
                $status >= 500 => "Daraja's STK Push gateway returned {$status} — try again in a moment.",
                default => "Daraja returned HTTP {$status}.",
            };

            return [
                'checkout_request_id' => null,
                'merchant_request_id' => null,
                'customer_message' => null,
                'raw' => is_array($body) ? $body : ['raw' => $bodyExcerpt],
                'error' => $reason,
            ];
        }

        // ResponseCode "0" = accepted by Daraja (push delivered). Anything else
        // is a synchronous failure.
        $responseCode = (string) ($body['ResponseCode'] ?? '');
        if ($responseCode !== '0') {
            return [
                'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
                'merchant_request_id' => $body['MerchantRequestID'] ?? null,
                'customer_message' => $body['CustomerMessage'] ?? $body['ResponseDescription'] ?? null,
                'raw' => $body,
                'error' => $body['errorMessage'] ?? $body['ResponseDescription'] ?? 'Daraja rejected the STK Push',
            ];
        }

        return [
            'checkout_request_id' => $body['CheckoutRequestID'] ?? null,
            'merchant_request_id' => $body['MerchantRequestID'] ?? null,
            'customer_message' => $body['CustomerMessage'] ?? null,
            'raw' => $body,
            'error' => null,
        ];
    }

    private function stkPushUrl(SafaricomCredential $credential): string {
        return $this->darajaBaseUrl($credential).'/mpesa/stkpush/v1/processrequest';
    }

    private function stkPushQueryUrl(SafaricomCredential $credential): string {
        return $this->darajaBaseUrl($credential).'/mpesa/stkpushquery/v1/query';
    }

    private function darajaBaseUrl(SafaricomCredential $credential): string {
        return $credential->isProduction()
            ? (string) config('safaricom-mobile-money.api.production_url', 'https://api.safaricom.co.ke')
            : (string) config('safaricom-mobile-money.api.sandbox_url', 'https://sandbox.safaricom.co.ke');
    }

    private function buildDefaultResultUrl(): string {
        $companyId = request()->attributes->get('companyId');
        $appUrl = rtrim((string) config('app.url'), '/');
        if ($appUrl === '' || !is_int($companyId)) {
            return '';
        }

        return $appUrl.'/api/safaricom/webhook/stk-push-result/'.$companyId;
    }

    /**
     * Accept the four common formats operators type and return the
     * Daraja-canonical 2547XXXXXXXX. Anything else is invalid.
     *
     *   254712345678  -> 254712345678
     *   +254712345678 -> 254712345678
     *   0712345678    -> 254712345678
     *   712345678     -> 254712345678
     *
     * Whitespace and hyphens are ignored. Only Safaricom (07XX, 011X) and
     * other prefixes that Daraja routes are accepted at the byte level; the
     * actual MNP routing happens at Daraja's side.
     */
    public function normalisePhoneNumber(string $raw): ?string {
        $digits = preg_replace('/[\s\-]+/', '', $raw) ?? $raw;
        $digits = ltrim($digits, '+');

        if (preg_match('/^254\d{9}$/', $digits) === 1) {
            return $digits;
        }
        if (preg_match('/^0\d{9}$/', $digits) === 1) {
            return '254'.substr($digits, 1);
        }
        if (preg_match('/^[17]\d{8}$/', $digits) === 1) {
            return '254'.$digits;
        }

        return null;
    }

    private function truncate(string $value, int $max): string {
        // Daraja rejects payloads where AccountReference > 12 or
        // TransactionDesc > 13 chars; multibyte-safe truncation keeps us
        // from accidentally chopping a UTF-8 sequence mid-byte.
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $max);
        }

        return substr($value, 0, $max);
    }

    public function validateMeterSerial(string $serialId): bool {
        return $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->exists();
    }

    public function validateSHSSerial(string $serialId): bool {
        return app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->exists();
    }

    public function validateDeviceSerial(string $serialId, string $deviceType = 'meter'): bool {
        if ($deviceType === 'solar_home_system') {
            return $this->validateSHSSerial($serialId);
        }

        return $this->validateMeterSerial($serialId);
    }

    public function getCustomerIdByMeterSerial(string $serialId): ?int {
        $meter = $this->meter->newQuery()
            ->where('serial_number', $serialId)
            ->where('in_use', 1)
            ->first();
        if (!$meter) {
            return null;
        }
        $person = $meter->device?->person;

        return $person ? (int) $person->id : null;
    }

    public function getCustomerIdBySHSSerial(string $serialId): ?int {
        $shs = app()->make(SolarHomeSystem::class)
            ->newQuery()
            ->where('serial_number', $serialId)
            ->first();
        if (!$shs) {
            return null;
        }
        $device = $shs->device()->first();
        $person = $device?->person;

        return $person ? (int) $person->id : null;
    }

    public function getCustomerIdByDeviceSerial(string $serialId, string $deviceType = 'meter'): ?int {
        return $deviceType === 'solar_home_system'
            ? $this->getCustomerIdBySHSSerial($serialId)
            : $this->getCustomerIdByMeterSerial($serialId);
    }

    public function getCustomerPhoneByCustomerId(int $customerId): ?string {
        try {
            $person = app()->make(PersonService::class)->getById($customerId);

            return (string) $person->addresses->first()->phone;
        } catch (\Exception) {
            return null;
        }
    }
}
