<?php

namespace App\Services\ImportServices;

use App\Enums\RateType;

final readonly class AppliancePersonImportItem {
    public function __construct(
        public string $customerName,
        public string $customerSurname,
        public string $applianceName,
        public string $paymentType,
        public ?int $totalCost,
        public ?int $rateCount,
        public ?RateType $rateType,
        public ?float $downPayment,
        public ?string $firstPaymentDate,
        public ?string $deviceSerial,
        public ?int $minimumPayableAmount,
        public ?int $pricePerDay,
        public int $creatorId,
    ) {}
}
