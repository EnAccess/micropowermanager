<?php

namespace App\Http\Controllers;

use App\Http\Resources\VodacomResource;
use App\Models\Transaction\VodacomTransaction;
use App\Services\VodacomService;
use Illuminate\Http\Request;

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
     * Validate a transaction request.
     *
     * @param Request $request
     *
     * @return VodacomResource
     */
    public function validateTransaction(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'serialNumber' => 'required|string',
            'amount' => 'required|numeric',
            'payerPhoneNumber' => 'required|string',
            'referenceId' => 'required|string',
        ]);

        try {
            $result = $this->vodacomService->validateTransaction($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }

    /**
     * Process a transaction.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function processTransaction(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'referenceId' => 'required|string',
            'transactionId' => 'required|string',
        ]);

        try {
            $result = $this->vodacomService->processTransaction($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }

    /**
     * Check transaction status.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function transactionEnquiryStatus(Request $request): VodacomResource {
        $validatedData = $request->validate([
            'referenceId' => 'required|string',
        ]);

        try {
            $result = $this->vodacomService->transactionEnquiryStatus($validatedData);

            return VodacomResource::make($result);
        } catch (\Exception $e) {
            return VodacomResource::error($e->getMessage());
        }
    }
}
