<?php

namespace App\Services\ImportServices;

use App\Enums\MpmPlugin;

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
        public ?MpmPlugin $smsGateway,
        public ?bool $transactionSmsEnabled,
        public ?int $downPaymentMaxTokenDays,
    ) {}
}
