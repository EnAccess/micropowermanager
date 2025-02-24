<?php

namespace App\Http\Controllers;

use App\Http\Resources\VodacomResource;
use App\Models\Transaction\VodacomTransaction;
use App\Services\VodacomService;
use Illuminate\Http\Request;

/**
 * @group Vodacom Transaction
 *
 * API endpoints for integrating with Vodacom's M-Pesa payment services
 */
class VodacomTransactionController extends Controller {
    public function __construct(private VodacomService $vodacomService) {}

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return void
     */
    public function store(Request $request): void {
        // get Transaction object
        $transactionData = request('transaction')->transaction;

        /** @var VodacomTransaction $vodacomTransaction */
        $vodacomTransaction = VodacomTransaction::query()->create([
            'conversation_id' => $transactionData->conversationID,
            'originator_conversation_id' => $transactionData->originatorConversationID,
            'mpesa_receipt' => $transactionData->mpesaReceipt,
            'transaction_date' => $transactionData->transactionDate,
            'transaction_id' => $transactionData->transactionID,
        ]);

        $vodacomTransaction->transaction()->create([
            'amount' => $transactionData->amount,
            'sender' => $transactionData->initiator,
            'message' => $transactionData->accountReference,
        ]);
    }

    /**
     * Validate Transaction.
     *
     * Validates a transaction before processing. Use this endpoint to verify if a transaction
     * can proceed based on the provided information. This is typically the first step in the payment flow.
     *
     * @bodyParam serialNumber string required Unique identifier for the product/service being purchased pattern: ^[A-Z0-9]{8,12}$ Example: ABC123456789
     * @bodyParam amount number required Transaction amount in the local currency  Example: 15000
     * @bodyParam payerPhoneNumber string required Customer's phone number in international format pattern: ^258[0-9]{9}$ Example: 258712345678
     * @bodyParam referenceId string required Unique reference identifier for this transaction pattern: ^[A-Za-z0-9\-]{5,20}$ Example: ORD-12345-ABC
     *
     * @response scenario="Success" {
     *   "data": {
     *     "transactionId": "VOD-TXN-123456",
     *     "status": "validated",
     *     "details": {
     *       "product": "Internet Bundle",
     *       "validAmount": true
     *     },
     *     "success": true
     *   }
     * }
     * @response 400 scenario="Validation Error" {
     *   "data": {
     *     "message": "Invalid amount specified for this product",
     *     "success": false
     *   }
     * }
     *
     * @param Request $request
     *
     * @return VodacomResource
     */
    public function validateTransaction(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'serialNumber' => 'required|string|regex:/^[A-Z0-9]{8,12}$/',
            'amount' => 'required|numeric|min:100|max:5000000',
            'payerPhoneNumber' => 'required|string|regex:/^258[0-9]{9}$/',
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
        ]);

        try {
            $result = $this->vodacomService->validateTransaction($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }

    /**
     * Process Transaction.
     *
     * Processes a previously validated transaction. This endpoint should be called after successful
     * validation to initiate the payment process with Vodacom M-Pesa.
     *
     * @bodyParam referenceId string required The reference ID used during validation pattern: ^[A-Za-z0-9\-]{5,20}$ Example: ORD-12345-ABC
     * @bodyParam transactionId string required The transaction ID returned from the validation step pattern: ^VOD-TXN-[0-9]{6}$ Example: VOD-TXN-123456
     *
     * @response scenario="Success" {
     *   "data": {
     *     "transactionId": "VOD-TXN-123456",
     *     "status": "processing",
     *     "providerReference": "MPESA-TX-987654321",
     *     "success": true
     *   }
     * }
     * @response 400 scenario="Processing Error" {
     *   "data": {
     *     "message": "Transaction processing failed: Insufficient funds",
     *     "success": false
     *   }
     * }
     *
     * @param Request $request
     *
     * @return VodacomResource
     */
    public function processTransaction(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
            'transactionId' => 'required|string|regex:/^VOD-TXN-[0-9]{6}$/',
        ]);

        try {
            $result = $this->vodacomService->processTransaction($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }

    /**
     * Check Transaction Status.
     *
     * Checks the current status of a transaction that has been submitted for processing.
     * Use this to verify if a payment has been completed, is still pending, or has failed.
     *
     * @bodyParam referenceId string required The reference ID of the transaction to check pattern: ^[A-Za-z0-9\-]{5,20}$ Example: ORD-12345-ABC
     *
     * @response scenario="Success" {
     *   "data": {
     *     "referenceId": "ORD-12345-ABC",
     *     "transactionId": "VOD-TXN-123456",
     *     "status": "completed",
     *     "mpesaReceipt": "QCL4521XYZ",
     *     "completedAt": "2023-06-15T12:45:32Z",
     *     "success": true
     *   }
     * }
     * @response 400 scenario="Enquiry Error" {
     *   "data": {
     *     "message": "Transaction not found or reference ID is invalid",
     *     "success": false
     *   }
     * }
     *
     * @param Request $request
     *
     * @return VodacomResource
     */
    public function transactionEnquiryStatus(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'referenceId' => 'required|string|regex:/^[A-Za-z0-9\-]{5,20}$/',
        ]);

        try {
            $result = $this->vodacomService->transactionEnquiryStatus($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }
}
