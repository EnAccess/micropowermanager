<?php

namespace App\Plugins\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\SteamaMeter\Jobs\SyncSteamaData;
use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Services\SteamaSyncActionService;
use App\Traits\ScheduledPluginCommand;
use Illuminate\Support\Carbon;

class SteamaMeterDataSynchronizer extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'steama-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Steamaco Meter.';

    public function __construct(
        private SteamaSyncActionService $steamaSyncActionService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Steamaco Meter Package #');
        $this->info('dataSync command started at '.Carbon::now()->toIso8601ZuluString());

        $this->steamaSyncActionService->getActionsNeedsToSync()->each(function (SteamaSyncAction $syncAction): void {
            $syncSetting = $syncAction->synSetting;
            if (!$syncSetting) {
                return;
            }

            dispatch(new SyncSteamaData($syncSetting->action_name));

            // Sites/Customers/Meters/Agents advance next_sync only after their job writes to the DB
            // successfully (SyncSteamaData::executeJob -> updateSyncAction), so a failed run stays due
            // and is retried on the next tick instead of waiting a full day. Transactions runs every
            // few minutes, so reserving its schedule on dispatch is fine.
            if ($syncSetting->action_name === 'Transactions') {
                $this->steamaSyncActionService->scheduleNextRun($syncAction, $syncSetting);
            }
        });

        $this->info('Took '.(microtime(true) - $timeStart).' seconds.');
        $this->info('#############################');
    }
}
