<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\PaymentHistoryService;

class MeterPaymentHistoryController {
    public function __construct(
        private PaymentHistoryService $paymentHistoryService,
    ) {}

    public function show(string $serialNumber): ApiResource {
        $paginate = request('paginate') ?? 50;

        return ApiResource::make($this->paymentHistoryService->getBySerialNumber($serialNumber, $paginate));
    }
}
