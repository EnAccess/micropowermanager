<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class MeterTransactionController {
    public function __construct(
        private TransactionService $transactionService,
    ) {}

    public function show(Request $request, string $serialNumber): ApiResource {
        return ApiResource::make($this->transactionService->getByDeviceSerial($serialNumber, $request->integer('paginate', 50)));
    }
}
