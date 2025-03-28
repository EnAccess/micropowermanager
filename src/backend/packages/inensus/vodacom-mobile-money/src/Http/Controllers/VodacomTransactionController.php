<?php

namespace Inensus\VodacomMobileMoney\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inensus\VodacomMobileMoney\Http\Requests\VodacomTransactionEnquiryStatusRequest;
use Inensus\VodacomMobileMoney\Http\Requests\VodacomTransactionProcessRequest;
use Inensus\VodacomMobileMoney\Http\Requests\VodacomTransactionValidationRequest;
use Inensus\VodacomMobileMoney\Http\Resources\VodacomTransactionResource;
use Inensus\VodacomMobileMoney\Services\VodacomTransactionService;

/**
 * @group Vodacom Transaction
 *
 * API endpoints for integrating with Vodacom's M-Pesa payment services
 */
class VodacomTransactionController extends Controller {
    public function __construct(private VodacomTransactionService $vodacomService) {}

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
    public function validateTransaction(VodacomTransactionValidationRequest $request): VodacomTransactionResource {
        $validatedData = $request->validated();

        try {
            $result = $this->vodacomService->validateTransaction($validatedData);

            return VodacomTransactionResource::make($result);
        } catch (\Exception $e) {
            return VodacomTransactionResource::error($e->getMessage());
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
    public function processTransaction(VodacomTransactionProcessRequest $request): VodacomTransactionResource {
        $validatedData = $request->validated();

        try {
            $result = $this->vodacomService->processTransaction($validatedData);

            return VodacomTransactionResource::make($result);
        } catch (\Exception $e) {
            return VodacomTransactionResource::error($e->getMessage());
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
    public function transactionEnquiryStatus(VodacomTransactionEnquiryStatusRequest $request): VodacomTransactionResource {
        $validatedData = $request->validated();

        try {
            $result = $this->vodacomService->transactionEnquiryStatus($validatedData);

            return VodacomTransactionResource::make($result);
        } catch (\Exception $e) {
            return VodacomTransactionResource::error($e->getMessage());
        }
    }
}
