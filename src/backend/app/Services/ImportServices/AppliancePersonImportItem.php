<?php

namespace App\Services\ImportServices;

final readonly class AppliancePersonImportItem {
    public function __construct(
        public string $customerName,
        public string $customerSurname,
        public string $applianceName,
        public string $paymentType,
        public ?int $totalCost,
        public ?int $rateCount,
        public ?string $rateType,
        public ?float $downPayment,
        public ?string $firstPaymentDate,
        public ?string $deviceSerial,
        public ?int $minimumPayableAmount,
        public ?int $pricePerDay,
        public int $creatorId,
    ) {}
}
