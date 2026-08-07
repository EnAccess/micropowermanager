<?php

namespace App\Plugins\FlutterwavePaymentProvider\Services;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
     * Flutterwave's webhook body is a flat object (`txRef`, `flwRef`, `status`,
     * `id`, ...), not the nested `{event, data}` shape their newer REST API
     * responses use — confirmed against a real delivery via ngrok's request
     * inspector.
     */
    public function processWebhook(Request $request, int $companyId): void {
        $payload = $request->all();

        if (($payload['status'] ?? null) === 'successful') {
            $this->handleSuccessfulPayment($payload, $companyId);
        } else {
            $this->handleFailedPayment($payload);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSuccessfulPayment(array $data, int $companyId): void {
        try {
            $reference = $data['txRef'] ?? null;
            if (!$reference) {
                return;
            }

            $flutterwaveTransaction = $this->transactionService->getByFlutterwaveReference($reference);
            if (!$flutterwaveTransaction instanceof FlutterwaveTransaction) {
                return;
            }

            $flutterwaveTransaction->external_transaction_id = (string) ($data['id'] ?? '');
            $flutterwaveTransaction->save();

            $this->transactionService->processSuccessfulPayment($companyId, $flutterwaveTransaction);
        } catch (\Exception $e) {
            Log::error('FlutterwaveWebhookService: Failed to process payment', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFailedPayment(array $data): void {
        $reference = $data['txRef'] ?? null;
        if (!$reference) {
            return;
        }

        $flutterwaveTransaction = $this->transactionService->getByFlutterwaveReference($reference);
        if (!$flutterwaveTransaction instanceof FlutterwaveTransaction) {
            return;
        }

        $flutterwaveTransaction->external_transaction_id = (string) ($data['id'] ?? '');
        $flutterwaveTransaction->save();

        $this->transactionService->processFailedPayment($flutterwaveTransaction);
    }
}
