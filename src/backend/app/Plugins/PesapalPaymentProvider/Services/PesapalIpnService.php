<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PesaPal IPN callbacks are not signed, so we never trust the inbound payload.
 * Every IPN triggers an authoritative GetTransactionStatus call before we
 * touch any transaction state — the same code path used by operator-driven
 * verify calls.
 */
class PesapalIpnService {
    public function __construct(
        private PesapalTransactionService $transactionService,
    ) {}

    public function processIpn(Request $request, int $companyId): bool {
        $orderTrackingId = $this->extractOrderTrackingId($request);
        if (in_array($orderTrackingId, [null, '', '0'], true)) {
            Log::warning('Pesapal IPN missing OrderTrackingId', ['payload' => $request->all()]);

            return false;
        }

        $pesapalTransaction = $this->transactionService->getByOrderTrackingId($orderTrackingId);
        if (!$pesapalTransaction instanceof PesapalTransaction) {
            Log::warning('Pesapal IPN for unknown order_tracking_id', ['order_tracking_id' => $orderTrackingId]);

            return false;
        }

        $status = $this->transactionService->syncStatusFromApi($pesapalTransaction, $companyId);
        if ($status['error'] !== null) {
            Log::error('Pesapal IPN status query failed', [
                'order_tracking_id' => $orderTrackingId,
                'error' => $status['error'],
            ]);

            return false;
        }

        return true;
    }

    private function extractOrderTrackingId(Request $request): ?string {
        return $request->input('OrderTrackingId')
            ?? $request->input('orderTrackingId')
            ?? $request->query('OrderTrackingId')
            ?? $request->query('orderTrackingId');
    }
}
