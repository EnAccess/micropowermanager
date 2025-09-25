<?php

namespace Inensus\SafaricomMobileMoney\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inensus\SafaricomMobileMoney\Services\SafaricomTransactionService;

class SafaricomWebhookController extends Controller {
    public function __construct(
        private SafaricomTransactionService $transactionService,
    ) {}

    /**
     * Handle STK Push result callback.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleSTKPushResult(Request $request) {
        try {
            $data = $request->all();

            // Validate the callback data
            if (!isset($data['Body']['stkCallback'])) {
                return response()->json(['message' => 'Invalid callback data'], 400);
            }

            $callbackData = $data['Body']['stkCallback'];
            $result = $callbackData['ResultDesc'];
            $resultCode = $callbackData['ResultCode'];

            // Extract transaction details
            $transactionData = [
                'ResultCode' => $resultCode,
                'ResultDesc' => $result,
                'AccountReference' => $callbackData['MerchantRequestID'] ?? null,
                'MpesaReceiptNumber' => $callbackData['CallbackMetadata']['Item'][1]['Value'] ?? null,
                'TransactionDate' => $callbackData['CallbackMetadata']['Item'][3]['Value'] ?? null,
            ];

            $this->transactionService->updateTransactionStatus($transactionData);

            return response()->json(['message' => 'Callback processed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle validation callback.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleValidation(Request $request) {
        // For validation, we just need to acknowledge receipt
        return response()->json([
            'ResultCode' => 0,
            'ResultDesc' => 'Success',
        ]);
    }

    /**
     * Handle confirmation callback.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleConfirmation(Request $request) {
        try {
            $data = $request->all();

            // Validate the callback data
            if (!isset($data['TransID'])) {
                return response()->json(['message' => 'Invalid callback data'], 400);
            }

            $transactionData = [
                'ResultCode' => 0, // Assuming success if we receive confirmation
                'AccountReference' => $data['BillRefNumber'] ?? null,
                'MpesaReceiptNumber' => $data['TransID'] ?? null,
                'TransactionDate' => $data['TransTime'] ?? null,
            ];

            $this->transactionService->updateTransactionStatus($transactionData);

            return response()->json(['message' => 'Confirmation processed successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
