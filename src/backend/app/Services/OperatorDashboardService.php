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
 * from those snapshots on read, so a single tenant can be refreshed without
 * walking the others and there is no stored roll-up that can drift from its
 * inputs. Reads never touch a tenant database; only {@see self::rebuild()} does.
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

        if (!$snapshot instanceof OperatorTenantSnapshot) {
            throw new OperatorDashboardTenantNotFoundException('No operator dashboard data for company '.$companyId.'.');
        }

        return $snapshot;
    }

    /**
     * Rebuilds every tenant's snapshot, or just one when a company is given.
     *
     * A tenant whose database cannot be read is logged and skipped: the platform
     * view must degrade to a stale row for that tenant rather than fail whole.
     */
    public function rebuild(?int $companyId = null): void {
        $pluginNames = MpmPlugin::query()->pluck('name', 'id')->all();
        $usageTypeNames = UsageType::query()->pluck('name', 'value')->all();

        if ($companyId !== null) {
            $this->rebuildCompany($companyId, $pluginNames, $usageTypeNames);
            $this->touchIndex([$companyId]);

            return;
        }

        $rebuiltCompanyIds = [];

        $this->databaseProxyManagerService->eachCompany(
            function (int $currentCompanyId) use (
                &$rebuiltCompanyIds,
                $pluginNames,
                $usageTypeNames
            ): void {
                $this->collectAndCache($currentCompanyId, $pluginNames, $usageTypeNames);
                $rebuiltCompanyIds[] = $currentCompanyId;
            },
            function (int $currentCompanyId, \Throwable $throwable): void {
                Log::error('Operator dashboard could not read company '.$currentCompanyId, [
                    'exception' => $throwable::class,
                    'message' => $throwable->getMessage(),
                ]);
            },
        );

        $this->replaceIndex($rebuiltCompanyIds);
    }

    public function isRefreshing(): bool {
        return Cache::has(self::REFRESHING_KEY);
    }

    public function markRefreshing(): void {
        Cache::put(
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
    private function rebuildCompany(int $companyId, array $pluginNames, array $usageTypeNames): void {
        $this->databaseProxyManagerService->runForCompany(
            $companyId,
            function () use ($companyId, $pluginNames, $usageTypeNames): void {
                $this->collectAndCache($companyId, $pluginNames, $usageTypeNames);
            }
        );
    }

    /**
     * @param array<int, string>    $pluginNames
     * @param array<string, string> $usageTypeNames
     */
    private function collectAndCache(int $companyId, array $pluginNames, array $usageTypeNames): void {
        $company = Company::query()->find($companyId);

        if ($company === null) {
            return;
        }

        Cache::put(
            self::TENANT_KEY_PREFIX.$companyId,
            $this->operatorTenantMetricsService->collect($company, $pluginNames, $usageTypeNames),
            $this->cacheTtl()
        );
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

        // A company removed between rebuilds leaves its id in the index, so misses
        // are expected and simply dropped.
        return array_values(array_filter(
            Cache::many($keys),
            fn (mixed $snapshot): bool => $snapshot instanceof OperatorTenantSnapshot
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

    /** @param list<int> $companyIds */
    private function replaceIndex(array $companyIds): void {
        Cache::put(
            self::INDEX_KEY,
            ['company_ids' => $companyIds, 'generated_at' => Carbon::now()->toIso8601String()],
            $this->cacheTtl()
        );
    }

    /**
     * Advances the freshness stamp after a single-tenant rebuild, keeping any
     * company ids the last full rebuild recorded.
     *
     * @param list<int> $companyIds
     */
    private function touchIndex(array $companyIds): void {
        $this->replaceIndex(array_values(array_unique([...$this->index()['company_ids'], ...$companyIds])));
    }

    private function cacheTtl(): Carbon {
        return Carbon::now()->addDays((int) config('micropowermanager.operator_dashboard.cache_ttl_days'));
    }
}
