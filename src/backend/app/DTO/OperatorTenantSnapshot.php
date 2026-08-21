<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enums\OperatorTenantHealth;
use Carbon\CarbonInterface;

class OperatorTenantSnapshot {
    /**
     * @param array{meters: int, shs: int, ebikes: int}                                        $devices
     * @param array<string, int>                                                               $monthlyTransactions           period => count
     * @param array<string, array<string, int>>                                                $monthlyTransactionsByProvider provider => period => count
     * @param list<string>                                                                     $plugins
     * @param list<array{type: string, at: string|null, count: int|null, detail: string|null}> $activity
     */
    public function __construct(
        public readonly int $companyId,
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $country,
        public readonly ?string $countryCode,
        public readonly ?string $usageType,
        public readonly ?CarbonInterface $registeredAt,
        public readonly ?CarbonInterface $lastActiveAt,
        public readonly int $customers,
        public readonly int $newCustomersThisMonth,
        public readonly array $devices,
        public readonly int $metersAssignedToCustomer,
        public readonly ?int $metersReportingLastSevenDays,
        public readonly array $monthlyTransactions,
        public readonly array $monthlyTransactionsByProvider,
        public readonly int $transactionsThisMonth,
        public readonly int $transactionsLastMonth,
        public readonly float $volumeThisMonth,
        public readonly ?string $currency,
        public readonly array $plugins,
        public readonly array $activity,
    ) {}

    public function health(): OperatorTenantHealth {
        return OperatorTenantHealth::fromLastActiveAt($this->lastActiveAt);
    }

    public function devicesTotal(): int {
        return $this->devices['meters'] + $this->devices['shs'] + $this->devices['ebikes'];
    }

    /** @return array<string, mixed> */
    public function toRowArray(): array {
        return [
            'id' => $this->companyId,
            'name' => $this->name,
            'country' => $this->country,
            'country_code' => $this->countryCode,
            'usage_type' => $this->usageType,
            'registered_at' => $this->registeredAt?->toIso8601String(),
            'last_active_at' => $this->lastActiveAt?->toIso8601String(),
            'health' => $this->health()->value,
            'customers' => $this->customers,
            'devices' => $this->devices + ['total' => $this->devicesTotal()],
            'transactions_this_month' => $this->transactionsThisMonth,
        ];
    }

    /** @return array<string, mixed> */
    public function toDetailArray(): array {
        return $this->toRowArray() + [
            'email' => $this->email,
            'phone' => $this->phone,
            'plugins' => $this->plugins,
            'monthly' => [
                'periods' => array_keys($this->monthlyTransactions),
                'transactions' => array_values($this->monthlyTransactions),
            ],
            'meters_assigned_to_customer' => $this->metersAssignedToCustomer,
            'meters_reporting_last_seven_days' => $this->metersReportingLastSevenDays,
            'volume_this_month' => $this->volumeThisMonth,
            'currency' => $this->currency,
            'activity' => $this->activity,
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array {
        return $this->toDetailArray();
    }
}
