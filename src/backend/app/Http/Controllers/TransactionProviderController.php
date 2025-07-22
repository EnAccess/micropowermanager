<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\TransactionProviderService;

class TransactionProviderController extends Controller {
    private TransactionProviderService $transactionProviderService;

    public function __construct(TransactionProviderService $transactionProviderService) {
        $this->transactionProviderService = $transactionProviderService;
    }

    public function index(): ApiResource {
        return new ApiResource($this->transactionProviderService->getTransactionProviders());
    }
}
