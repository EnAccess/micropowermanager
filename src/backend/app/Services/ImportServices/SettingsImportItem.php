<?php

namespace App\Services\ImportServices;

final readonly class SettingsImportItem {
    public function __construct(
        public ?string $siteTitle,
        public ?string $companyName,
        public ?string $currency,
        public ?string $country,
        public ?string $language,
        public ?float $vatEnergy,
        public ?float $vatAppliance,
        public ?string $usageType,
        public ?string $smsGatewayId,
        public ?bool $transactionSmsEnabled,
    ) {}
}
