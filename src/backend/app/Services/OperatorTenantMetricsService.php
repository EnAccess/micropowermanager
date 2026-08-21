<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\OperatorPlatformSnapshot;
use App\DTO\OperatorTenantSnapshot;
use App\Enums\DeviceType;
use App\Models\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Propaganistas\LaravelPhone\PhoneNumber;

/**
 * Collects one tenant's figures for the operator dashboard.
 *
 * The operator dashboard is the only consumer that reads every tenant's
 * database, so all of its tenant-side SQL is confined to this class and the
 * orchestrating service never issues a tenant query itself. Every method here
 * assumes the `tenant` connection is already bound to the company being read.
 */
class OperatorTenantMetricsService {
    private const string COUNTRY_NAMES_PATH = 'data/countries.json';

    /** @var array<string, string>|null */
    private ?array $countryNames = null;

    /**
     * @param array<int, string>    $pluginNamesByMpmPluginId
     * @param array<string, string> $usageTypeNamesByValue
     */
    public function collect(
        Company $company,
        array $pluginNamesByMpmPluginId,
        array $usageTypeNamesByValue,
    ): OperatorTenantSnapshot {
        $periods = OperatorPlatformSnapshot::periods();
        $currentPeriod = Carbon::now()->format('Y-m');
        $previousPeriod = Carbon::now()->subMonthNoOverflow()->format('Y-m');

        $customers = $this->customerCounts();
        $devices = $this->deviceCounts();
        $meterReporting = $this->meterReportingCounts();
        $transactions = $this->transactionSeries($periods);
        $settings = $this->settings();
        $countryCode = $this->countryCode($company->phone);

        $monthlyTransactions = $transactions['per_period'];
        $transactionsThisMonth = $monthlyTransactions[$currentPeriod] ?? 0;

        return new OperatorTenantSnapshot(
            companyId: $company->id,
            name: $company->name,
            email: $company->email,
            phone: $company->phone,
            country: $countryCode === null ? null : $this->countryName($countryCode),
            countryCode: $countryCode,
            usageType: $this->usageTypeLabel($settings['usage_type'], $devices, $usageTypeNamesByValue),
            registeredAt: $company->created_at,
            lastActiveAt: $this->lastActiveAt(),
            customers: $customers['total'],
            newCustomersThisMonth: $customers['new_this_month'],
            devices: $devices,
            metersAssignedToCustomer: $this->metersAssignedToCustomer(),
            metersReportingLastSevenDays: $meterReporting,
            monthlyTransactions: $monthlyTransactions,
            monthlyTransactionsByProvider: $transactions['per_period_by_provider'],
            transactionsThisMonth: $transactionsThisMonth,
            transactionsLastMonth: $monthlyTransactions[$previousPeriod] ?? 0,
            volumeThisMonth: $transactions['volume_per_period'][$currentPeriod] ?? 0.0,
            currency: $settings['currency'],
            plugins: $this->pluginNames($pluginNamesByMpmPluginId),
            activity: $this->activity($transactionsThisMonth, $customers['new_this_month'], $meterReporting),
        );
    }

    /** @return array{total: int, new_this_month: int} */
    private function customerCounts(): array {
        $row = DB::connection('tenant')->table('people')
            ->whereNull('deleted_at')
            ->where('is_customer', 1)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(created_at >= ?) AS new_this_month', [Carbon::now()->startOfMonth()])
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'new_this_month' => (int) ($row->new_this_month ?? 0),
        ];
    }

    /** @return array{meters: int, shs: int, ebikes: int} */
    private function deviceCounts(): array {
        $countsByType = DB::connection('tenant')->table('devices')
            ->groupBy('device_type')
            ->select('device_type')
            ->selectRaw('COUNT(*) AS devices')
            ->pluck('devices', 'device_type')
            ->all();

        return [
            'meters' => (int) ($countsByType[DeviceType::Meter->value] ?? 0),
            'shs' => (int) ($countsByType[DeviceType::SolarHomeSystem->value] ?? 0),
            'ebikes' => (int) ($countsByType[DeviceType::EBike->value] ?? 0),
        ];
    }

    private function metersAssignedToCustomer(): int {
        // `meters.in_use` is only maintained by the bulk registration and SparkMeter
        // paths, so assignment to a customer is the portable definition of a meter
        // that is actually in the field.
        return DB::connection('tenant')->table('devices')
            ->where('device_type', DeviceType::Meter->value)
            ->whereNotNull('person_id')
            ->count();
    }

    /**
     * Null when the tenant has no consumption records at all: only some meter
     * manufacturers report readings back, and rendering a zero would look like a
     * fleet-wide outage rather than an absent integration.
     */
    private function meterReportingCounts(): ?int {
        $row = DB::connection('tenant')->table('meter_consumptions')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(DISTINCT CASE WHEN reading_date >= ? THEN meter_id END) AS reporting', [
                Carbon::now()->subDays(7),
            ])
            ->first();

        if ((int) ($row->total ?? 0) === 0) {
            return null;
        }

        return (int) ($row->reporting ?? 0);
    }

    /**
     * @param list<string> $periods
     *
     * @return array{
     *     per_period: array<string, int>,
     *     per_period_by_provider: array<string, array<string, int>>,
     *     volume_per_period: array<string, float>,
     * }
     */
    private function transactionSeries(array $periods): array {
        $rows = DB::connection('tenant')->table('transactions')
            ->where('created_at', '>=', Carbon::parse($periods[0].'-01')->startOfDay())
            ->groupBy('period', 'provider')
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS period")
            ->selectRaw('original_transaction_type AS provider')
            ->selectRaw('COUNT(*) AS transactions')
            ->selectRaw('SUM(amount) AS volume')
            ->get();

        $perPeriod = array_fill_keys($periods, 0);
        $volumePerPeriod = array_fill_keys($periods, 0.0);
        $perPeriodByProvider = [];

        foreach ($rows as $row) {
            if (!array_key_exists((string) $row->period, $perPeriod)) {
                continue;
            }

            $perPeriod[$row->period] += (int) $row->transactions;
            $volumePerPeriod[$row->period] += (float) $row->volume;

            // `original_transaction_type` is the payment provider, as a morph alias.
            // Aliases only register when their plugin boots, so unknown values are
            // passed through untouched rather than resolved to a class.
            $provider = (string) ($row->provider ?? 'unknown');
            $perPeriodByProvider[$provider] ??= array_fill_keys($periods, 0);
            $perPeriodByProvider[$provider][$row->period] += (int) $row->transactions;
        }

        return [
            'per_period' => $perPeriod,
            'per_period_by_provider' => $perPeriodByProvider,
            'volume_per_period' => $volumePerPeriod,
        ];
    }

    private function lastActiveAt(): ?Carbon {
        $lastTransactionAt = DB::connection('tenant')->table('transactions')->max('created_at');

        if ($lastTransactionAt === null) {
            return null;
        }

        return Carbon::parse($lastTransactionAt);
    }

    /** @return array{currency: string|null, usage_type: string|null} */
    private function settings(): array {
        $row = DB::connection('tenant')->table('main_settings')
            ->select('currency', 'usage_type')
            ->first();

        return [
            'currency' => $row->currency ?? null,
            'usage_type' => $row->usage_type ?? null,
        ];
    }

    /**
     * @param array<int, string> $pluginNamesByMpmPluginId
     *
     * @return list<string>
     */
    private function pluginNames(array $pluginNamesByMpmPluginId): array {
        $enabledIds = DB::connection('tenant')->table('plugins')
            ->where('status', 1)
            ->pluck('mpm_plugin_id')
            ->all();

        $names = [];
        foreach ($enabledIds as $id) {
            if (isset($pluginNamesByMpmPluginId[(int) $id])) {
                $names[] = $pluginNamesByMpmPluginId[(int) $id];
            }
        }

        sort($names);

        return $names;
    }

    /**
     * @param array{meters: int, shs: int, ebikes: int} $devices
     * @param array<string, string>                     $usageTypeNamesByValue
     */
    private function usageTypeLabel(?string $usageType, array $devices, array $usageTypeNamesByValue): ?string {
        if ($usageType !== null && isset($usageTypeNamesByValue[$usageType])) {
            return $usageTypeNamesByValue[$usageType];
        }

        $derived = [];
        if ($devices['meters'] > 0) {
            $derived[] = 'mini-grid';
        }
        if ($devices['shs'] > 0) {
            $derived[] = 'shs';
        }
        if ($devices['ebikes'] > 0) {
            $derived[] = 'e-bike';
        }

        return $usageTypeNamesByValue[implode('&', $derived)] ?? null;
    }

    private function countryCode(?string $phone): ?string {
        if ($phone === null || trim($phone) === '') {
            return null;
        }

        // `companies.country_id` points at a per-tenant `countries` row and is left
        // at its -1 default in practice, and `main_settings.country` defaults to a
        // fixed value, so the dialling code is the only trustworthy signal.
        return new PhoneNumber($phone)->getCountry();
    }

    private function countryName(string $countryCode): ?string {
        if ($this->countryNames === null) {
            $contents = file_get_contents(resource_path(self::COUNTRY_NAMES_PATH));
            $decoded = $contents === false ? null : json_decode($contents, true);
            $this->countryNames = is_array($decoded) ? $decoded : [];
        }

        return $this->countryNames[$countryCode] ?? null;
    }

    /** @return list<array{type: string, at: string|null, count: int|null, detail: string|null}> */
    private function activity(int $transactionsThisMonth, int $newCustomersThisMonth, ?int $metersReporting): array {
        $lastTariffChangeAt = DB::connection('tenant')->table('tariffs')->max('updated_at');
        $lastSmsSentAt = DB::connection('tenant')->table('sms')->where('direction', 1)->max('created_at');

        $entries = [
            [
                'type' => 'payments_this_month',
                'at' => null,
                'count' => $transactionsThisMonth,
                'detail' => null,
            ],
            [
                'type' => 'meters_reporting',
                'at' => null,
                'count' => $metersReporting,
                'detail' => null,
            ],
            [
                'type' => 'customers_onboarded',
                'at' => null,
                'count' => $newCustomersThisMonth,
                'detail' => null,
            ],
            [
                'type' => 'tariff_updated',
                'at' => $lastTariffChangeAt === null ? null : Carbon::parse($lastTariffChangeAt)->toIso8601String(),
                'count' => null,
                'detail' => null,
            ],
            [
                // There is no batch or campaign entity behind the SMS table, so this
                // is the last message sent, not a batch.
                'type' => 'sms_sent',
                'at' => $lastSmsSentAt === null ? null : Carbon::parse($lastSmsSentAt)->toIso8601String(),
                'count' => null,
                'detail' => null,
            ],
        ];

        return array_slice($entries, 0, (int) config('micropowermanager.operator_dashboard.activity_entries'));
    }
}
