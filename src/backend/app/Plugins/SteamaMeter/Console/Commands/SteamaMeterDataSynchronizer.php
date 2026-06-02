<?php

namespace App\Plugins\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\SteamaMeter\Jobs\SyncSteamaData;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use App\Plugins\SteamaMeter\Services\SteamaSyncActionService;
use App\Plugins\SteamaMeter\Services\SteamaSyncSettingService;
use App\Traits\ScheduledPluginCommand;
use Illuminate\Support\Carbon;

class SteamaMeterDataSynchronizer extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'steama-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Steamaco Meter.';

    public function __construct(
        private SteamaSyncSettingService $steamaSyncSettingService,
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

        $syncActions = $this->steamaSyncActionService->getActionsNeedsToSync();

        $this->steamaSyncSettingService->getSyncSettings()->each(function (SteamaSyncSetting $syncSetting) use ($syncActions): true {
            $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
            if (!$syncAction) {
                return true;
            }

            dispatch(new SyncSteamaData($syncSetting->action_name));
            $this->steamaSyncActionService->scheduleNextRun($syncAction, $syncSetting);

            return true;
        });

        $this->info('Took '.(microtime(true) - $timeStart).' seconds.');
        $this->info('#############################');
    }
}
