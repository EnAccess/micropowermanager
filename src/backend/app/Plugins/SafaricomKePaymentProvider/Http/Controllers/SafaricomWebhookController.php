<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Http\Controllers;

use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

#[Group('Plugins / Safaricom Ke')]
class SafaricomWebhookController extends Controller {
    public function __construct(
        private SafaricomTransactionService $transactionService,
    ) {}

    /**
     * Daraja STK Push result callback.
     *
     * Daraja payload shape:
     *   Body.stkCallback.MerchantRequestID
     *   Body.stkCallback.CheckoutRequestID
     *   Body.stkCallback.ResultCode  (0 = success, anything else = failure)
     *   Body.stkCallback.ResultDesc
     *   Body.stkCallback.CallbackMetadata.Item[] (only present on success)
     */
    public function handleSTKPushResult(Request $request, ?int $companyId = null): JsonResponse {
        $companyId ??= (int) $request->attributes->get('companyId');
        $payload = $request->all();

        $stkCallback = $payload['Body']['stkCallback'] ?? null;
        if (!is_array($stkCallback)) {
            Log::warning('Safaricom STK callback malformed', ['payload' => $payload]);

            return $this->ack();
        }

        $checkoutRequestId = (string) ($stkCallback['CheckoutRequestID'] ?? '');
        $transaction = $checkoutRequestId !== ''
            ? $this->transactionService->getByCheckoutRequestId($checkoutRequestId)
            : null;

        if (!$transaction instanceof SafaricomTransaction) {
            Log::warning('Safaricom STK callback for unknown CheckoutRequestID', [
                'checkout_request_id' => $checkoutRequestId,
            ]);

            return $this->ack();
        }

        $resultCode = (int) ($stkCallback['ResultCode'] ?? -1);
        $applyPayload = [
            'result_desc' => $stkCallback['ResultDesc'] ?? null,
            'result_code' => $resultCode,
            'mpesa_receipt' => $this->extractCallbackItem($stkCallback, 'MpesaReceiptNumber'),
            'transaction_date' => $this->normaliseTransactionDate(
                $this->extractCallbackItem($stkCallback, 'TransactionDate'),
            ),
            'phone_number' => $this->extractCallbackItem($stkCallback, 'PhoneNumber'),
            'amount' => $this->extractCallbackItem($stkCallback, 'Amount'),
        ];

        $this->transactionService->applyResultCode($transaction, $resultCode, $applyPayload, $companyId);

        return $this->ack();
    }

    public function handleValidation(): JsonResponse {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Success']);
    }

    public function handleConfirmation(Request $request): JsonResponse {
        Log::info('Safaricom C2B confirmation received', $request->all());

        return $this->ack();
    }

    private function ack(): JsonResponse {
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * @param array<string, mixed> $stkCallback
     */
    private function extractCallbackItem(array $stkCallback, string $name): mixed {
        $items = $stkCallback['CallbackMetadata']['Item'] ?? [];
        if (!is_array($items)) {
            return null;
        }
        foreach ($items as $item) {
            if (is_array($item) && ($item['Name'] ?? null) === $name) {
                return $item['Value'] ?? null;
            }
        }

        return null;
    }

    /**
     * Daraja sends transaction dates as `YYYYMMDDHHmmss` integers.
     */
    private function normaliseTransactionDate(mixed $raw): ?string {
        if (!is_string($raw) && !is_int($raw)) {
            return null;
        }
        $str = (string) $raw;
        if (preg_match('/^\d{14}$/', $str) !== 1) {
            return null;
        }

        return substr($str, 0, 4).'-'.substr($str, 4, 2).'-'.substr($str, 6, 2)
            .' '.substr($str, 8, 2).':'.substr($str, 10, 2).':'.substr($str, 12, 2);
    }
}
