<?php

namespace App\Plugins\FlutterwavePaymentProvider\Services;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use Illuminate\Http\Request;

class FlutterwaveWebhookService {
    public function __construct(
        private FlutterwaveCredentialService $credentialService,
        private FlutterwaveTransactionService $transactionService,
    ) {}

    /**
     * Flutterwave doesn't sign webhooks with an HMAC — it echoes the dashboard's
     * "Secret Hash" back verbatim in a `verif-hash` header, and expects a direct
     * string comparison against the value configured for that account. Confirmed
     * against real webhook deliveries: with no Secret Hash set, no header is
     * sent at all; with one set, `verif-hash` carries the hash unmodified (no
     * `flutterwave-signature` header, no HMAC involved).
     */
    public function verifyWebhook(Request $request): bool {
        $credential = $this->credentialService->getCredentials();
        $webhookSecretHash = $credential->getWebhookSecretHash();

        if ($webhookSecretHash === '' || $webhookSecretHash === '0') {
            return false;
        }

        $signature = $request->header('verif-hash');
        if (empty($signature)) {
            return false;
        }

        return hash_equals($webhookSecretHash, $signature);
    }

    /**
     * @return bool whether the notification was recognized and settled; the caller
     *              uses this to distinguish "processed" from "ignored" in its response
     */
    public function processWebhook(Request $request, int $companyId): bool {
        $payload = $this->normalizePayload($request->all());

        return ($payload['status'] ?? null) === 'successful'
            ? $this->handleSuccessfulPayment($payload, $companyId)
            : $this->handleFailedPayment($payload);
    }

    /**
     * Flutterwave sends webhooks in one of two shapes, toggled per-account on
     * the dashboard ("v3 webhooks"): the older shape is a flat object (`txRef`,
     * `flwRef`, `status`, `amount`, `currency`, `id`, ...) with no wrapper; the
     * newer shape nests those same fields (snake_cased: `tx_ref`, `amount`,
     * `currency`, `status`, `id`) under a `data` key, alongside sibling
     * `event`/`event.type` keys — confirmed the same way. Both are remapped
     * here to the flat shape below, since that's what the rest of this class
     * and `FlutterwaveTransactionService::verifyCharge()` read; nothing here
     * needs the fields Flutterwave sends but this integration never uses
     * so only the five that matter are
     * carried over.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizePayload(array $payload): array {
        if (!isset($payload['data'], $payload['event']) || !is_array($payload['data'])) {
            return $payload;
        }

        $data = $payload['data'];

        return [
            'id' => $data['id'] ?? null,
            'txRef' => $data['tx_ref'] ?? null,
            'status' => $data['status'] ?? null,
            'amount' => $data['amount'] ?? null,
            'currency' => $data['currency'] ?? null,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSuccessfulPayment(array $data, int $companyId): bool {
        $flutterwaveTransaction = $this->findTransaction($data);
        if (!$flutterwaveTransaction instanceof FlutterwaveTransaction) {
            return false;
        }

        if (!$this->transactionService->verifyCharge($flutterwaveTransaction, $data)) {
            return false;
        }

        $flutterwaveTransaction->external_transaction_id = (string) ($data['id'] ?? '');
        $this->transactionService->processSuccessfulPayment($companyId, $flutterwaveTransaction);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findTransaction(array $data): ?FlutterwaveTransaction {
        $reference = $data['txRef'] ?? null;
        if (!is_string($reference) || $reference === '') {
            return null;
        }

        return $this->transactionService->getByFlutterwaveReference($reference);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFailedPayment(array $data): bool {
        $flutterwaveTransaction = $this->findTransaction($data);
        if (!$flutterwaveTransaction instanceof FlutterwaveTransaction) {
            return false;
        }

        $flutterwaveTransaction->external_transaction_id = (string) ($data['id'] ?? '');
        $this->transactionService->processFailedPayment($flutterwaveTransaction);

        return true;
    }
}
