<?php

namespace App\Plugins\PaystackPaymentProvider\Services;

use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use Illuminate\Http\Request;

class PaystackWebhookService {
    public function __construct(
        private PaystackCredentialService $credentialService,
        private PaystackTransactionService $transactionService,
    ) {}

    public function verifyWebhook(Request $request): bool {
        $credential = $this->credentialService->getCredentials();
        $secretKey = $credential->getSecretKey();

        if ($secretKey === '' || $secretKey === '0') {
            return false;
        }

        $signature = $request->header('X-Paystack-Signature');
        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    public function processWebhook(Request $request, int $companyId): bool {
        $event = $request->string('event')->toString();
        $data = $request->array('data');

        return match ($event) {
            'charge.success' => $this->handleSuccessfulPayment($data, $companyId),
            'charge.failed' => $this->handleFailedPayment($data),
            default => false,
        };
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleSuccessfulPayment(array $data, int $companyId): bool {
        $paystackTransaction = $this->findTransaction($data);
        if (!$paystackTransaction instanceof PaystackTransaction) {
            return false;
        }

        if (!$this->transactionService->verifyCharge($paystackTransaction, $data)) {
            return false;
        }

        $paystackTransaction->external_transaction_id = (string) ($data['id'] ?? '');
        $this->transactionService->processSuccessfulPayment($companyId, $paystackTransaction);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleFailedPayment(array $data): bool {
        $paystackTransaction = $this->findTransaction($data);
        if (!$paystackTransaction instanceof PaystackTransaction) {
            return false;
        }

        $paystackTransaction->external_transaction_id = (string) ($data['id'] ?? '');
        $this->transactionService->processFailedPayment($paystackTransaction);

        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function findTransaction(array $data): ?PaystackTransaction {
        $reference = $data['reference'] ?? null;
        if (!is_string($reference) || $reference === '') {
            return null;
        }

        return $this->transactionService->getByPaystackReference($reference);
    }
}
