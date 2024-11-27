<?php

namespace MPM\Transaction;

use App\Jobs\ApplianceTransactionProcessor;
use App\Jobs\EnergyTransactionProcessor;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use MPM\Device\DeviceService;

class TransactionPaymentProcessor {
    protected const PROCESSORS_BY_DEVICE_TYPE = [
        Meter::RELATION_NAME => EnergyTransactionProcessor::class,
        SolarHomeSystem::RELATION_NAME => ApplianceTransactionProcessor::class,
        EBike::RELATION_NAME => ApplianceTransactionProcessor::class,
    ];
    protected const QUEUE_BY_DEVICE_TYPE = [
        Meter::RELATION_NAME => 'energy',
        SolarHomeSystem::RELATION_NAME => 'payment',
        EBike::RELATION_NAME => 'payment',
    ];

    public static function process(int $transactionId): void {
        $transactionService = app()->make(TransactionService::class);
        $transaction = $transactionService->getById($transactionId);
        $serialNumber = $transaction->message;
        $deviceService = app()->make(DeviceService::class);
        $device = $deviceService->getBySerialNumber($serialNumber);
        $deviceType = $device->device_type;
        $processorClass = self::PROCESSORS_BY_DEVICE_TYPE[$deviceType];

        // Instantiate the processor class
        $processor = new $processorClass($transactionId);

        $queue = self::QUEUE_BY_DEVICE_TYPE[$deviceType];
        // Dispatch the job
        $processor::dispatch($transactionId)
            ->allOnConnection('redis')
            ->onQueue(config("services.queues.$queue"));
    }
}
