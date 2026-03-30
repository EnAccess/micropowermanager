<?php

namespace App\Plugins\Prospect\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\Prospect\Jobs\ExtractAgents;
use App\Plugins\Prospect\Jobs\ExtractCustomers;
use App\Plugins\Prospect\Jobs\ExtractInstallations;
use App\Plugins\Prospect\Jobs\ExtractPayments;
use App\Plugins\Prospect\Jobs\PushAgents;
use App\Plugins\Prospect\Jobs\PushCustomers;
use App\Plugins\Prospect\Jobs\PushInstallations;
use App\Plugins\Prospect\Jobs\PushPayments;
use App\Plugins\Prospect\Models\ProspectSyncSetting;
use App\Plugins\Prospect\Services\ProspectSyncActionService;
use App\Plugins\Prospect\Services\ProspectSyncSettingService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;

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
            if (!$syncSetting->is_enabled) {
                return true;
            }

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
                } elseif ($actionName === 'payments') {
                    dispatch(new ExtractPayments());
                    dispatch(new PushPayments());
                    $result = true;
                } elseif ($actionName === 'customers') {
                    dispatch(new ExtractCustomers());
                    dispatch(new PushCustomers());
                    $result = true;
                } elseif ($actionName === 'agents') {
                    dispatch(new ExtractAgents());
                    dispatch(new PushAgents());
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
