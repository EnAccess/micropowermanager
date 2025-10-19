<?php

namespace Inensus\SparkMeter\Services;

use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Date;
use Inensus\SparkMeter\Models\SmSyncAction;

class SmSyncActionService {
    public function __construct(private SmSyncAction $syncAction) {}

    public function createSyncAction(array $syncAction) {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId($settingId) {
        return $this->syncAction->newQuery()->where('sync_setting_id', $settingId)->first();
    }

    public function getActionsNeedsToSync() {
        return $this->syncAction->newQuery()->where('next_sync', '<=', Date::now())->orderBy('next_sync')->get();
    }

    public function updateSyncAction($syncAction, $syncSetting, $syncResult) {
        if (!$syncResult) {
            return $syncAction->update([
                'attempts' => $syncAction->attempts + 1,
                'last_sync' => Date::now(),
            ]);
        }
        $interval = CarbonInterval::make($syncSetting->sync_in_value_num.$syncSetting->sync_in_value_str);

        return $syncAction->update([
            'attempts' => 0,
            'last_sync' => Date::now(),
            'next_sync' => Date::now()->add($interval),
        ]);
    }
}
