<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Services;

use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\PesapalApiService;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\GetTransactionStatusResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * PesaPal IPN callbacks are not signed, so we never trust the inbound payload.
 * Every IPN triggers an authoritative GetTransactionStatus call before we
 * touch any transaction state.
 */
class PesapalIpnService {
    public function __construct(
        private PesapalApiService $apiService,
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

        $status = $this->apiService->getTransactionStatus($orderTrackingId);
        if ($status['error'] !== null) {
            Log::error('Pesapal IPN status query failed', [
                'order_tracking_id' => $orderTrackingId,
                'error' => $status['error'],
            ]);

            return false;
        }

        if (!empty($status['confirmation_code'])) {
            $pesapalTransaction->setExternalTransactionId($status['confirmation_code']);
            $pesapalTransaction->save();
        }

        return $this->applyStatus($pesapalTransaction, $status['status_code'], $companyId);
    }

    private function extractOrderTrackingId(Request $request): ?string {
        return $request->input('OrderTrackingId')
            ?? $request->input('orderTrackingId')
            ?? $request->query('OrderTrackingId')
            ?? $request->query('orderTrackingId');
    }

    private function applyStatus(PesapalTransaction $transaction, ?int $statusCode, int $companyId): bool {
        switch ($statusCode) {
            case GetTransactionStatusResource::STATUS_COMPLETED:
                $this->transactionService->processSuccessfulPayment($companyId, $transaction);

                return true;
            case GetTransactionStatusResource::STATUS_FAILED:
            case GetTransactionStatusResource::STATUS_REVERSED:
                $this->transactionService->processFailedPayment($transaction);

                return true;
            case GetTransactionStatusResource::STATUS_INVALID:
                $transaction->setStatus(PesapalTransaction::STATUS_ABANDONED);
                $transaction->save();

                return true;
            default:
                Log::info('Pesapal IPN received unrecognized status', [
                    'order_tracking_id' => $transaction->getOrderTrackingId(),
                    'status_code' => $statusCode,
                ]);

                return false;
        }
    }
}
