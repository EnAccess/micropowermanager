<?php

namespace Inensus\KelinMeter\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Inensus\KelinMeter\Models\KelinSyncAction;

class KelinSyncActionService {
    private $syncAction;

    public function __construct(KelinSyncAction $syncAction) {
        $this->syncAction = $syncAction;
    }

    public function createSyncAction($syncAction) {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId($settingId) {
        return $this->syncAction->newQuery()->where('sync_setting_id', $settingId)->first();
    }

    public function getActionsNeedsToSync() {
        return $this->syncAction->newQuery()->where('next_sync', '<=', Carbon::now())->orderBy('next_sync')->get();
    }

    public function updateSyncAction($syncAction, $syncSetting, $syncResult) {
        if (!$syncResult) {
            return $syncAction->update([
                'attempts' => $syncAction->attempts + 1,
                'last_sync' => Carbon::now(),
            ]);
        }
        $interval = CarbonInterval::make($syncSetting->sync_in_value_num.$syncSetting->sync_in_value_str);

        return $syncAction->update([
            'attempts' => 0,
            'last_sync' => Carbon::now(),
            'next_sync' => Carbon::now()->add($interval),
        ]);
    }
}
