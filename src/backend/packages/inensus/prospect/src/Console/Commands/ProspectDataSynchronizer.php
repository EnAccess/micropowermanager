<?php

namespace Inensus\Prospect\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\Prospect\Jobs\ExtractInstallations;
use Inensus\Prospect\Jobs\PushInstallations;
use Inensus\Prospect\Models\ProspectSyncSetting;
use Inensus\Prospect\Services\ProspectSyncActionService;
use Inensus\Prospect\Services\ProspectSyncSettingService;

class ProspectDataSynchronizer extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 24;

    protected $signature = 'prospect:dataSync';
    protected $description = 'Synchronize Prospect data based on saved sync settings';

    public function __construct(
        private ProspectSyncSettingService $syncSettingService,
        private ProspectSyncActionService $syncActionService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Prospect Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at '.$startedAt);

        $syncActions = $this->syncActionService->getActionsNeedsToSync();

        $this->syncSettingService->updateSyncSettings([]);

        $this->syncSettingService->getSyncSettings()->each(function (ProspectSyncSetting $syncSetting) use ($syncActions): true {
            $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
            if (!$syncAction) {
                return true;
            }

            $actionName = strtolower($syncSetting->action_name);

            $result = false;
            try {
                if ($actionName === 'installations') {
                    dispatch(new ExtractInstallations());
                    dispatch(new PushInstallations());
                    $result = true;
                }
            } catch (\Exception) {
                $result = false;
            }

            $this->syncActionService->updateSyncAction($syncAction, $syncSetting, $result);

            return true;
        });

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
