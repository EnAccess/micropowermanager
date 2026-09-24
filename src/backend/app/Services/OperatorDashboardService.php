<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\OperatorPlatformSnapshot;
use App\DTO\OperatorTenantSnapshot;
use App\Exceptions\OperatorDashboardTenantNotFoundException;
use App\Models\Company;
use App\Models\MpmPlugin;
use App\Models\UsageType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Reads and rebuilds the operator dashboard's cross-tenant aggregate.
 *
 * Every tenant is cached as its own snapshot and the platform roll-up is folded
 * from those snapshots on read, so there is no stored roll-up that can drift
 * from its inputs. Reads never touch a tenant database; only
 * {@see self::rebuild()} does.
 */
class OperatorDashboardService {
    private const string TENANT_KEY_PREFIX = 'operator-dashboard:tenant:';
    private const string INDEX_KEY = 'operator-dashboard:index';
    private const string REFRESHING_KEY = 'operator-dashboard:refreshing';

    public function __construct(
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private OperatorTenantMetricsService $operatorTenantMetricsService,
    ) {}

    public function platformSnapshot(): OperatorPlatformSnapshot {
        $index = $this->index();

        return OperatorPlatformSnapshot::fold(
            $this->tenantSnapshots($index['company_ids']),
            $index['generated_at'] === null ? null : Carbon::parse($index['generated_at']),
            $this->isRefreshing(),
        );
    }

    public function tenantSnapshot(int $companyId): OperatorTenantSnapshot {
        $snapshot = Cache::get(self::TENANT_KEY_PREFIX.$companyId);

        if (!is_array($snapshot)) {
            throw new OperatorDashboardTenantNotFoundException('No operator dashboard data for company '.$companyId.'.');
        }

        return OperatorTenantSnapshot::fromArray($snapshot);
    }

    /**
     * Rebuilds every tenant's snapshot.
     *
     * A tenant whose database cannot be read is logged and skipped: the platform
     * view must degrade to a stale row for that tenant rather than fail whole.
     * Snapshots never expire, so those of companies that no longer exist are
     * removed here.
     */
    public function rebuild(): void {
        $pluginNames = MpmPlugin::query()->pluck('name', 'id')->all();
        $usageTypeNames = UsageType::query()->pluck('name', 'value')->all();

        $indexedCompanyIds = [];

        $this->databaseProxyManagerService->eachCompany(
            function (int $currentCompanyId) use (
                &$indexedCompanyIds,
                $pluginNames,
                $usageTypeNames
            ): void {
                if ($this->collectAndCache($currentCompanyId, $pluginNames, $usageTypeNames)) {
                    $indexedCompanyIds[] = $currentCompanyId;
                }
            },
            function (int $currentCompanyId, \Throwable $throwable) use (&$indexedCompanyIds): void {
                Log::error('Operator dashboard could not read company '.$currentCompanyId, [
                    'exception' => $throwable::class,
                    'message' => $throwable->getMessage(),
                ]);
                $indexedCompanyIds[] = $currentCompanyId;
            },
        );

        foreach (array_diff($this->index()['company_ids'], $indexedCompanyIds) as $removedCompanyId) {
            Cache::forget(self::TENANT_KEY_PREFIX.$removedCompanyId);
        }

        Cache::forever(self::INDEX_KEY, [
            'company_ids' => $indexedCompanyIds,
            'generated_at' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function isRefreshing(): bool {
        return Cache::has(self::REFRESHING_KEY);
    }

    /** Returns false when another rebuild already holds the flag. */
    public function startRefreshing(): bool {
        return Cache::add(
            self::REFRESHING_KEY,
            true,
            Carbon::now()->addMinutes((int) config('micropowermanager.operator_dashboard.refreshing_ttl_minutes'))
        );
    }

    public function clearRefreshing(): void {
        Cache::forget(self::REFRESHING_KEY);
    }

    public function generatedAt(): ?Carbon {
        $generatedAt = $this->index()['generated_at'];

        return $generatedAt === null ? null : Carbon::parse($generatedAt);
    }

    /**
     * @param array<int, string>    $pluginNames
     * @param array<string, string> $usageTypeNames
     */
    private function collectAndCache(int $companyId, array $pluginNames, array $usageTypeNames): bool {
        $company = Company::query()->find($companyId);

        if ($company === null) {
            return false;
        }

        Cache::forever(
            self::TENANT_KEY_PREFIX.$companyId,
            $this->operatorTenantMetricsService->collect($company, $pluginNames, $usageTypeNames)->toArray()
        );

        return true;
    }

    /**
     * @param list<int> $companyIds
     *
     * @return list<OperatorTenantSnapshot>
     */
    private function tenantSnapshots(array $companyIds): array {
        if ($companyIds === []) {
            return [];
        }

        $keys = array_map(fn (int $companyId): string => self::TENANT_KEY_PREFIX.$companyId, $companyIds);

        // A tenant that failed on its first rebuild is indexed without a snapshot,
        // so misses are expected and simply dropped.
        return array_values(array_map(
            OperatorTenantSnapshot::fromArray(...),
            array_filter(Cache::many($keys), is_array(...))
        ));
    }

    /** @return array{company_ids: list<int>, generated_at: string|null} */
    private function index(): array {
        $index = Cache::get(self::INDEX_KEY);

        if (!is_array($index)) {
            return ['company_ids' => [], 'generated_at' => null];
        }

        return [
            'company_ids' => array_values(array_map(intval(...), $index['company_ids'] ?? [])),
            'generated_at' => $index['generated_at'] ?? null,
        ];
    }
}
