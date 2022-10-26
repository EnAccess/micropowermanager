<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterService;
use App\Services\PaymentHistoryService;
use Illuminate\Http\Request;

class MeterPaymentHistoryController
{
    public function __construct(
        private PaymentHistoryService $paymentHistoryService
    ) {
    }

    public function show(string $serialNumber)
    {
        $paginate = request('paginate') ?? 1;

        return ApiResource::make($this->paymentHistoryService->getBySerialNumber($serialNumber, $paginate));
    }
}
