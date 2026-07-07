<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionEnquiryStatusRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionProcessRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomTransactionValidationRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Resources\VodacomTransactionResource;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzTransactionService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Plugins / Vodacom Mz', "API endpoints for integrating with Vodacom's M-Pesa payment services")]
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
