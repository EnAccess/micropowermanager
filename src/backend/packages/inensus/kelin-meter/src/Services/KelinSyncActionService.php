<?php

namespace Inensus\KelinMeter\Services;

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Inensus\KelinMeter\Models\KelinSyncAction;
use Inensus\KelinMeter\Models\KelinSyncSetting;

class KelinSyncActionService {
    public function __construct(
        private KelinSyncAction $syncAction,
    ) {}

    /**
     * @param array<string, mixed> $syncAction
     */
    public function createSyncAction(array $syncAction): KelinSyncAction {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId(int $settingId): ?KelinSyncAction {
        return $this->syncAction->newQuery()->where('sync_setting_id', $settingId)->first();
    }

    /**
     * @return Collection<int, KelinSyncAction>
     */
    public function getActionsNeedsToSync(): Collection {
        return $this->syncAction->newQuery()->where('next_sync', '<=', Date::now())->orderBy('next_sync')->get();
    }

    public function updateSyncAction(KelinSyncAction $syncAction, KelinSyncSetting $syncSetting, bool $syncResult): bool {
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
