<?php

namespace App\DTO;

/**
 * Filters for the people list endpoint.
 *
 * A `null` property means "do not filter on this attribute".
 */
readonly class PersonListFilters {
    public function __construct(
        public int $isCustomer = 1,
        public ?int $agentId = null,
        public ?bool $activeCustomer = null,
        public ?int $cityId = null,
        public ?float $totalPaidMin = null,
        public ?float $totalPaidMax = null,
        public ?string $latestPaymentFrom = null,
        public ?string $latestPaymentTo = null,
        public ?string $registrationFrom = null,
        public ?string $registrationTo = null,
        public ?string $deviceType = null,
    ) {}
}
