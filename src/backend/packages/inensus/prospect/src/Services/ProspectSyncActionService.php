<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Inensus\Prospect\Models\ProspectSyncAction;
use Inensus\Prospect\Models\ProspectSyncSetting;

class ProspectSyncActionService {
    public function __construct(private ProspectSyncAction $syncAction) {}

    /**
     * @param array<string, mixed> $syncAction
     */
    public function createSyncAction(array $syncAction): ProspectSyncAction {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId(int $settingId): ?ProspectSyncAction {
        return $this->syncAction->newQuery()->where('sync_setting_id', $settingId)->first();
    }

    /**
     * @return Collection<int, ProspectSyncAction>
     */
    public function getActionsNeedsToSync(): Collection {
        return $this->syncAction->newQuery()->where('next_sync', '<=', Carbon::now())->orderBy('next_sync')->get();
    }

    public function updateSyncAction(ProspectSyncAction $syncAction, ProspectSyncSetting $syncSetting, bool $syncResult): bool {
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
