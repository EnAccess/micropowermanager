<?php

namespace App\Plugins\SparkMeter\Jobs;

use App\Jobs\AbstractJob;
use App\Plugins\SparkMeter\Services\CustomerService;
use App\Plugins\SparkMeter\Services\MeterModelService;
use App\Plugins\SparkMeter\Services\SiteService;
use App\Plugins\SparkMeter\Services\SmSalesAccoutService;
use App\Plugins\SparkMeter\Services\SmSyncActionService;
use App\Plugins\SparkMeter\Services\SmSyncSettingService;
use App\Plugins\SparkMeter\Services\TariffService;
use App\Plugins\SparkMeter\Services\TransactionService;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncSparkMeterData extends AbstractJob {
    /**
     * Mirrors SteamaMeter's SyncSteamaData: SparkMeter syncs page through the remote API too, so
     * the default 60s queue timeout would kill a slow run mid-way. The redis connection's
     * retry_after is kept above this so a slow run is not duplicated.
     */
    public int $timeout = 600;

    public function __construct(private string $actionName, ?int $companyId = null) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('spark_meter');
    }

    /**
     * Stops a slow run (longer than the dataSync command's cadence) from being picked up a second
     * time for the same tenant before the first finishes; the overlapping dispatch is dropped, not
     * requeued, and the next tick re-evaluates once the running job has completed.
     *
     * @return array<int, object>
     */
    public function middleware(): array {
        return [new WithoutOverlapping($this->actionName.'-'.$this->companyId)->dontRelease()->expireAfter($this->timeout)];
    }

    public function executeJob(): void {
        $syncActionService = resolve(SmSyncActionService::class);
        $syncSetting = resolve(SmSyncSettingService::class)->getSyncSettingsByActionName($this->actionName);
        $syncAction = $syncActionService->getSyncActionBySynSettingId($syncSetting->id);

        try {
            match ($this->actionName) {
                'Sites' => resolve(SiteService::class)->sync(),
                'MeterModels' => resolve(MeterModelService::class)->sync(),
                'Tariffs' => resolve(TariffService::class)->sync(),
                'SalesAccounts' => resolve(SmSalesAccoutService::class)->sync(),
                'Customers' => resolve(CustomerService::class)->sync(),
                'Transactions' => resolve(TransactionService::class)->sync(),
                default => throw new \InvalidArgumentException("Unknown SparkMeter sync action: {$this->actionName}"),
            };
            if ($syncAction) {
                $syncActionService->updateSyncAction($syncAction, $syncSetting, true);
            }
        } catch (\Throwable $e) {
            if ($syncAction) {
                $syncActionService->updateSyncAction($syncAction, $syncSetting, false);
            }
            throw $e;
        }
    }
}
