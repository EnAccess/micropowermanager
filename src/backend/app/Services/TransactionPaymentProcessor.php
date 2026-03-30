<?php

namespace App\Services;

use App\Exceptions\TransactionNotMatchedException;
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
        $serialId = $transaction->message;
        $deviceService = app()->make(DeviceService::class);
        $device = $deviceService->getBySerialNumber($serialId);

        if ($device !== null) {
            $deviceType = $device->device_type;

            // select the correct processor and instantiate the processor class
            $processorClass = self::PROCESSORS_BY_DEVICE_TYPE[$deviceType];

            $processorClass::dispatch($companyId, $transactionId);

            return;
        }

        if ($transaction->nonPaygoAppliance()->exists()) {
            dispatch(new ApplianceTransactionProcessor($companyId, $transactionId));

            return;
        }

        throw new TransactionNotMatchedException("No device or appliance found for serial id: {$serialId}");
    }
}
