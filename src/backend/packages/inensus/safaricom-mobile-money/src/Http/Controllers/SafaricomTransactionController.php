<?php

namespace Inensus\SafaricomMobileMoney\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use Inensus\SafaricomMobileMoney\Http\Requests\SafaricomSTKPushRequest;
use Inensus\SafaricomMobileMoney\Services\SafaricomTransactionService;

class SafaricomTransactionController extends Controller {
    public function __construct(
        private SafaricomTransactionService $transactionService,
    ) {}

    /**
     * Initiate STK Push payment.
     *
     * @param SafaricomSTKPushRequest $request
     *
     * @return ApiResource
     */
    public function initiateSTKPush(SafaricomSTKPushRequest $request): ApiResource {
        try {
            $result = $this->transactionService->initiateSTKPush($request->validated());

            return ApiResource::make($result);
        } catch (\Exception $e) {
            return ApiResource::make(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Check transaction status.
     *
     * @param string $referenceId
     *
     * @return ApiResource
     */
    public function checkStatus(string $referenceId): ApiResource {
        try {
            $result = $this->transactionService->checkTransactionStatus($referenceId);

            return ApiResource::make($result);
        } catch (\Exception $e) {
            return ApiResource::make(['message' => $e->getMessage()], 400);
        }
    }
}
