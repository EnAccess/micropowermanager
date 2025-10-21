<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\TransactionProviderService;

class TransactionProviderController extends Controller {
    public function __construct(private TransactionProviderService $transactionProviderService) {}

    public function index(): ApiResource {
        return new ApiResource($this->transactionProviderService->getTransactionProviders());
    }
}
