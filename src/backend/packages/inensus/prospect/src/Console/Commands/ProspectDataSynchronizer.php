<?php

namespace Inensus\Prospect\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\Prospect\Jobs\ExtractInstallations;
use Inensus\Prospect\Jobs\PushInstallations;
use Inensus\Prospect\Services\ProspectSyncActionService;
use Inensus\Prospect\Services\ProspectSyncSettingService;

class ProspectDataSynchronizer extends Command {
    protected $signature = 'prospect:dataSync';
    protected $description = 'Synchronize Prospect data based on saved sync settings';

    public function __construct(
        private ProspectSyncSettingService $syncSettingService,
        private ProspectSyncActionService $syncActionService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('prospect:dataSync started at '.$startedAt);

        $syncActions = $this->syncActionService->getActionsNeedsToSync();

        $this->syncSettingService->updateSyncSettings([]); // no-op; ensure service is wired

        $this->syncSettingService->getSyncSettings()->each(function ($syncSetting) use ($syncActions): true {
            $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
            if (!$syncAction) {
                return true;
            }

            $actionName = strtolower($syncSetting->action_name);

            $result = false;
            try {
                if ($actionName === 'installations' || $actionName === 'installation') {
                    ExtractInstallations::dispatch();
                    PushInstallations::dispatch();
                    $result = true;
                }
            } catch (\Exception) {
                $result = false;
            }

            $this->syncActionService->updateSyncAction($syncAction, $syncSetting, $result);

            return true;
        });

        $this->info('prospect:dataSync finished');
    }
}
