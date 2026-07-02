<?php

namespace App\Services\ImportServices;

final readonly class ApplianceImportItem {
    public function __construct(
        public string $applianceName,
        public ?string $applianceType,
        public int $price,
    ) {}
}
