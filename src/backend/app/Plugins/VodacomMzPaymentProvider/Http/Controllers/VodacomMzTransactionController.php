<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionEnquiryStatusRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionProcessRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionValidationRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Resources\VodacomTransactionResource;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzTransactionService;
use Dedoc\Scramble\Attributes\Group;

/**
 * @group Vodacom Transaction
 *
 * API endpoints for integrating with Vodacom's M-Pesa payment services
 */
#[Group('Plugins / Vodacom Mz')]
class VodacomMzTransactionController extends Controller {
    public function __construct(
        private VodacomMzTransactionService $vodacomService,
    ) {}

    /**
     * Validate Transaction.
     *
     * Validates a transaction before processing. Use this endpoint to verify if a transaction
     * can proceed based on the provided information. This is typically the first step in the payment flow.
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
     */
    public function queryTransactionStatus(VodacomTransactionEnquiryStatusRequest $request): VodacomTransactionResource {
        $validatedData = $request->validated();

        try {
            $result = $this->vodacomService->transactionEnquiryStatus($validatedData);

            return VodacomTransactionResource::make($result);
        } catch (\Exception $e) {
            return VodacomTransactionResource::error($e->getMessage());
        }
    }
}
