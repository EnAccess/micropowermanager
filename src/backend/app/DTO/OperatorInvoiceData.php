<?php

declare(strict_types=1);

namespace App\DTO;

use App\Models\Company;
use Carbon\CarbonInterface;

/**
 * One tenant's usage for a billing month and the MPM Cloud tier it lands in.
 */
class OperatorInvoiceData {
    private const string CUSTOM_TIER = 'Custom';

    private function __construct(
        public readonly int $companyId,
        public readonly string $companyName,
        public readonly ?string $companyEmail,
        public readonly CarbonInterface $monthStart,
        public readonly int $customers,
        public readonly int $devices,
        public readonly int $transactions,
        public readonly string $tier,
        public readonly ?int $monthlyPriceUsd,
        public readonly bool $aboveFreeTier,
    ) {}

    /**
     * The tier is the first one whose every limit covers the usage.
     *
     * @param array{customers: int, devices: int, transactions: int} $usage
     */
    public static function forUsage(Company $company, CarbonInterface $monthStart, array $usage): self {
        /** @var list<array{name: string, price_usd: int, customers: int, devices: int, transactions: int}> $tiers */
        $tiers = config('micropowermanager.operator_dashboard.billing_tiers');

        $tierIndex = null;
        foreach ($tiers as $index => $tier) {
            if ($usage['customers'] <= $tier['customers']
                && $usage['devices'] <= $tier['devices']
                && $usage['transactions'] <= $tier['transactions']) {
                $tierIndex = $index;
                break;
            }
        }

        return new self(
            companyId: $company->id,
            companyName: $company->name,
            companyEmail: $company->email,
            monthStart: $monthStart,
            customers: $usage['customers'],
            devices: $usage['devices'],
            transactions: $usage['transactions'],
            tier: $tierIndex === null ? self::CUSTOM_TIER : $tiers[$tierIndex]['name'],
            monthlyPriceUsd: $tierIndex === null ? null : $tiers[$tierIndex]['price_usd'],
            aboveFreeTier: $tierIndex !== 0,
        );
    }

    public function filename(): string {
        return 'invoice-data_'.str($this->companyName)->slug().'_'.$this->monthStart->format('Y-m').'.csv';
    }

    /** @return list<list<int|string|null>> */
    public function toCsvRows(): array {
        return [
            [
                'tenant_id',
                'tenant_name',
                'tenant_email',
                'billing_month',
                'customers',
                'devices',
                'transactions',
                'tier',
                'monthly_price_usd',
                'above_free_tier',
            ],
            [
                $this->companyId,
                $this->companyName,
                $this->companyEmail,
                $this->monthStart->format('Y-m'),
                $this->customers,
                $this->devices,
                $this->transactions,
                $this->tier,
                $this->monthlyPriceUsd,
                $this->aboveFreeTier ? 'yes' : 'no',
            ],
        ];
    }
}
