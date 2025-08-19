<?php

declare(strict_types=1);

namespace MPM\TenantResolver\ApiResolvers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inensus\PaystackPaymentProvider\Modules\Transaction\PaystackTransactionService;

class PaystackApiResolver implements ApiResolverInterface {
    public function __construct(
        private PaystackTransactionService $transactionService,
    ) {}

    public function resolveCompanyId(Request $request): int {
        // For webhook callbacks, try to get company ID from transaction reference
        if ($request->isMethod('POST') && $request->path() === 'api/paystack/webhook') {
            return $this->resolveFromWebhook($request);
        }

        // For other Paystack API calls, try to get from JWT token
        return $this->resolveFromJWT($request);
    }

    private function resolveFromWebhook(Request $request): int {
        $payload = $request->all();
        $data = $payload['data'] ?? [];
        $reference = $data['reference'] ?? null;

        if (!$reference) {
            throw ValidationException::withMessages(['webhook' => 'failed to parse reference from Paystack webhook']);
        }

        $transaction = $this->transactionService->getByPaystackReference($reference);
        if (!$transaction) {
            throw ValidationException::withMessages(['webhook' => 'transaction not found for reference: '.$reference]);
        }

        // Get company ID from the transaction's metadata or order_id
        $companyId = $this->getCompanyIdFromTransaction($transaction);
        if (!$companyId) {
            throw ValidationException::withMessages(['webhook' => 'failed to determine company from transaction']);
        }

        return $companyId;
    }

    private function resolveFromJWT(Request $request): int {
        /** @var \Tymon\JWTAuth\JWTGuard $guard */
        $guard = auth('api');

        if (!$guard->check()) {
            throw ValidationException::withMessages(['authentication' => 'JWT token required for Paystack API access']);
        }

        $companyId = $guard->payload()->get('companyId');
        if (!$companyId) {
            throw ValidationException::withMessages(['companyId' => 'failed to parse company identifier from JWT token']);
        }

        return (int) $companyId;
    }

    private function getCompanyIdFromTransaction($transaction): ?int {
        // Try to get company ID from transaction metadata
        $metadata = $transaction->metadata;
        if (is_array($metadata) && isset($metadata['company_id'])) {
            return (int) $metadata['company_id'];
        }

        // Try to get from order_id format (assuming format: companyId_orderDetails)
        $orderId = $transaction->getOrderId();
        if (preg_match('/^(\d+)_/', $orderId, $matches)) {
            return (int) $matches[1];
        }

        // Try to get from reference_id format
        $referenceId = $transaction->getReferenceId();
        if (preg_match('/^(\d+)_/', $referenceId, $matches)) {
            return (int) $matches[1];
        }

        // As a fallback, try to get from the database connection name
        $connection = $transaction->getConnectionName();
        if (preg_match('/tenant_(\d+)/', $connection, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
