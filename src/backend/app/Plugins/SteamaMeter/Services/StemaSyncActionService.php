<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;

class StemaSyncActionService {
    public function __construct(
        private SteamaSyncAction $syncAction,
    ) {}

    /**
     * @param array<string, mixed> $syncAction
     */
    public function createSyncAction(array $syncAction): SteamaSyncAction {
        return $this->syncAction->newQuery()->create($syncAction);
    }

    public function getSyncActionBySynSettingId(int $settingId): ?SteamaSyncAction {
        return $this->syncAction->newQuery()
            ->where('sync_setting_id', $settingId)
            ->first();
    }

    /**
     * @return Collection<int, SteamaSyncAction>
     */
    public function getActionsNeedsToSync(): Collection {
        return $this->syncAction->newQuery()
            ->where('next_sync', '<=', Carbon::now())
            ->orderBy('next_sync')
            ->get();
    }

    public function updateSyncAction(SteamaSyncAction $syncAction, SteamaSyncSetting $syncSetting, bool $syncResult): bool {
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

    /**
     * Advances only the schedule (next_sync) without touching the attempts counter, so the
     * dispatching command can space out runs while the queued job remains the source of truth
     * for success/failure tracking on the sync-action row.
     */
    public function scheduleNextRun(SteamaSyncAction $syncAction, SteamaSyncSetting $syncSetting): bool {
        $interval = CarbonInterval::make($syncSetting->sync_in_value_num.$syncSetting->sync_in_value_str);

        return $syncAction->update([
            'next_sync' => Carbon::now()->add($interval),
        ]);
    }
}
