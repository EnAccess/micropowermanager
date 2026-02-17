<?php

namespace App\Plugins\SparkMeter\Services;

use App\Plugins\SparkMeter\Models\SmSyncAction;
use App\Plugins\SparkMeter\Models\SmSyncSetting;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;

class SmSyncActionService {
    public function __construct(
        private SmSyncAction $syncAction,
    ) {}

    /**
     * @param array<string, mixed> $syncAction
     */
    public function createSyncAction(array $syncAction): SmSyncAction {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId(int $settingId): ?SmSyncAction {
        return $this->syncAction->newQuery()->where('sync_setting_id', $settingId)->first();
    }

    /**
     * @return Collection<int, SmSyncAction>
     */
    public function getActionsNeedsToSync(): Collection {
        return $this->syncAction->newQuery()->where('next_sync', '<=', Carbon::now())->orderBy('next_sync')->get();
    }

    public function updateSyncAction(SmSyncAction $syncAction, SmSyncSetting $syncSetting, bool $syncResult): bool {
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
