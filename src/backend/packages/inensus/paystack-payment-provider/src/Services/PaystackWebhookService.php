<?php

namespace Inensus\PaystackPaymentProvider\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;

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

    public function processWebhook(Request $request, int $companyId): void {
        $payload = $request->all();
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if ($event === 'charge.success') {
            $this->handleSuccessfulPayment($data, $companyId);
        } elseif ($event === 'charge.failed') {
            $this->handleFailedPayment($data);
        }
    }

    private function handleSuccessfulPayment(array $data, int $companyId): void {
        try {
            $reference = $data['reference'] ?? null;
            if (!$reference) {
                return;
            }

            $paystackTransaction = $this->transactionService->getByPaystackReference($reference);
            if (!$paystackTransaction) {
                return;
            }

            $paystackTransaction->setExternalTransactionId($data['id'] ?? '');

            // Get customer's phone number for sender field
            $customerPhone = $this->transactionService->getCustomerPhoneByCustomerId($paystackTransaction->getCustomerId());
            $sender = $customerPhone ?: "";
            
            $paystackTransaction->transaction()->create([
                'amount' => $paystackTransaction->getAmount(),
                'sender' => $sender,
                'message' => $paystackTransaction->getDeviceSerial(),
                'type' => 'energy',
            ]);
            $paystackTransaction->save();
            // Process the successful payment in MPM
            $this->transactionService->processSuccessfulPayment($companyId, $paystackTransaction);
        } catch (\Exception $e) {
            Log::error('PaystackWebhookService: Failed to process payment', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function handleFailedPayment(array $data): void {
        $reference = $data['reference'] ?? null;
        if (!$reference) {
            return;
        }

        $paystackTransaction = $this->transactionService->getByPaystackReference($reference);
        if (!$paystackTransaction) {
            return;
        }

        $paystackTransaction->setStatus(PaystackTransaction::STATUS_FAILED);
        $paystackTransaction->save();
    }
}
