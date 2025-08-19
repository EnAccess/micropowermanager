<?php

namespace Inensus\PaystackPaymentProvider\Services;

use Illuminate\Http\Request;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Transaction\PaystackTransactionService;

class PaystackWebhookService {
    public function __construct(
        private PaystackCredentialService $credentialService,
        private PaystackTransactionService $transactionService,
    ) {}

    public function verifyWebhook(Request $request): bool {
        $credential = $this->credentialService->getCredentials();
        $webhookSecret = $credential->getWebhookSecret();

        if (empty($webhookSecret)) {
            return false;
        }

        $signature = $request->header('X-Paystack-Signature');
        if (empty($signature)) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha512', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    public function processWebhook(Request $request): void {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if ($event === 'charge.success') {
            $this->handleSuccessfulPayment($data);
        } elseif ($event === 'charge.failed') {
            $this->handleFailedPayment($data);
        }
    }

    private function handleSuccessfulPayment(array $data): void {
        $reference = $data['reference'] ?? null;
        if (!$reference) {
            return;
        }

        $transaction = $this->transactionService->getByPaystackReference($reference);
        if (!$transaction) {
            return;
        }

        $transaction->setStatus(PaystackTransaction::STATUS_SUCCESS);
        $transaction->setExternalTransactionId($data['id'] ?? '');
        $transaction->save();

        // Process the successful payment in MPM
        $this->transactionService->processSuccessfulPayment($transaction);
    }

    private function handleFailedPayment(array $data): void {
        $reference = $data['reference'] ?? null;
        if (!$reference) {
            return;
        }

        $transaction = $this->transactionService->getByPaystackReference($reference);
        if (!$transaction) {
            return;
        }

        $transaction->setStatus(PaystackTransaction::STATUS_FAILED);
        $transaction->save();
    }
}
