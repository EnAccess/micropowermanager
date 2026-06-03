<?php

namespace App\Plugins\SteamaMeter\Jobs;

use App\Jobs\AbstractJob;
use App\Plugins\SteamaMeter\Services\SteamaAgentService;
use App\Plugins\SteamaMeter\Services\SteamaCustomerService;
use App\Plugins\SteamaMeter\Services\SteamaMeterService;
use App\Plugins\SteamaMeter\Services\SteamaSiteService;
use App\Plugins\SteamaMeter\Services\SteamaSyncActionService;
use App\Plugins\SteamaMeter\Services\SteamaSyncSettingService;
use App\Plugins\SteamaMeter\Services\SteamaTransactionsService;

class SyncSteamaData extends AbstractJob {
    /**
     * Steama syncs paginate the remote API (each request up to request_timeout = 120s), so the
     * default 60s queue timeout kills them mid-run. Allow up to 10 minutes; the redis connection's
     * retry_after is kept above this so a slow run is not duplicated.
     */
    public int $timeout = 600;

    public function __construct(private string $actionName, ?int $companyId = null) {
        parent::__construct($companyId);

        $this->onConnection('redis');
        $this->onQueue('steama_meter');
    }

    public function executeJob(): void {
        $syncActionService = app(SteamaSyncActionService::class);
        $syncSetting = app(SteamaSyncSettingService::class)->getSyncSettingsByActionName($this->actionName);
        $syncAction = $syncActionService->getSyncActionBySynSettingId($syncSetting->id);

        try {
            match ($this->actionName) {
                'Sites' => app(SteamaSiteService::class)->sync(),
                'Customers' => app(SteamaCustomerService::class)->sync(),
                'Meters' => app(SteamaMeterService::class)->sync(),
                'Agents' => app(SteamaAgentService::class)->sync(),
                'Transactions' => app(SteamaTransactionsService::class)->sync(),
                default => throw new \InvalidArgumentException("Unknown Steama sync action: {$this->actionName}"),
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
