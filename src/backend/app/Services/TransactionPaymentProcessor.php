<?php

namespace App\Services;

use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\EnergyTransactionProcessor;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;

class TransactionPaymentProcessor {
    protected const PROCESSORS_BY_DEVICE_TYPE = [
        Meter::RELATION_NAME => EnergyTransactionProcessor::class,
        SolarHomeSystem::RELATION_NAME => ApplianceTransactionProcessor::class,
        EBike::RELATION_NAME => ApplianceTransactionProcessor::class,
    ];

    public static function process(int $companyId, int $transactionId): void {
        $transactionService = app()->make(TransactionService::class);
        $transaction = $transactionService->getById($transactionId);
        $serialNumber = $transaction->message;
        $deviceService = app()->make(DeviceService::class);
        $device = $deviceService->getBySerialNumber($serialNumber);
        $deviceType = $device->device_type;

        // select the correct processor and instantiate the processor class
        $processorClass = self::PROCESSORS_BY_DEVICE_TYPE[$deviceType];

        // Dispatch the job
        $processorClass::dispatch($companyId, $transactionId);
    }
}
