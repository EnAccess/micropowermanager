<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Plugins\SteamaMeter\Exceptions\ModelNotFoundException;
use App\Plugins\SteamaMeter\Models\SteamaSetting;
use App\Plugins\SteamaMeter\Models\SteamaSyncAction;
use App\Plugins\SteamaMeter\Models\SteamaSyncSetting;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;

class SteamaSyncSettingService {
    public function __construct(
        private SteamaSyncSetting $syncSetting,
        private SteamaSetting $setting,
        private SteamaSyncActionService $syncActionService,
    ) {}

    public function createDefaultSettings(): void {
        $defaultSyncSettings = [
            ['action_name' => 'Sites', 'sync_in_value_num' => 1, 'sync_in_value_str' => 'day'],
            ['action_name' => 'Customers', 'sync_in_value_num' => 1, 'sync_in_value_str' => 'day'],
            ['action_name' => 'Meters', 'sync_in_value_num' => 1, 'sync_in_value_str' => 'day'],
            ['action_name' => 'Agents', 'sync_in_value_num' => 1, 'sync_in_value_str' => 'day'],
            ['action_name' => 'Transactions', 'sync_in_value_num' => 5, 'sync_in_value_str' => 'minute'],
        ];

        foreach ($defaultSyncSettings as $defaultSyncSetting) {
            $syncSetting = $this->syncSetting->newQuery()->firstOrCreate(
                ['action_name' => $defaultSyncSetting['action_name']],
                [
                    'sync_in_value_str' => $defaultSyncSetting['sync_in_value_str'],
                    'sync_in_value_num' => $defaultSyncSetting['sync_in_value_num'],
                ],
            );

            if ($syncSetting->wasRecentlyCreated) {
                $setting = $this->setting->newQuery()->make();
                $setting->setting()->associate($syncSetting);
                $setting->save();
            }

            if (!$this->syncActionService->getSyncActionBySynSettingId($syncSetting->id) instanceof SteamaSyncAction) {
                $this->syncActionService->createSyncAction([
                    'sync_setting_id' => $syncSetting->id,
                    'next_sync' => Carbon::now(),
                ]);
            }
        }
    }

    /**
     * @param array<string, array<string, mixed>> $syncSettings
     *
     * @return Collection<int, SteamaSyncSetting>
     */
    public function updateSyncSettings(array $syncSettings): Collection {
        foreach ($syncSettings as $setting) {
            $syncSetting = $this->syncSetting->newQuery()->find($setting['id']);
            $intervalStr = $setting['sync_in_value_num'].$setting['sync_in_value_str'];
            $syncSettingAction = $this->syncActionService->getSyncActionBySynSettingId($setting['id']);

            if ($syncSetting) {
                $date = Carbon::now();
                $interval = CarbonInterval::make($intervalStr);

                $syncSetting->update([
                    'max_attempts' => $setting['max_attempts'],
                    'sync_in_value_str' => $setting['sync_in_value_str'],
                    'sync_in_value_num' => $setting['sync_in_value_num'],
                ]);

                $syncSettingAction->update([
                    'next_sync' => $date->add($interval),
                ]);
            }
        }

        return $this->syncSetting->newQuery()->get();
    }

    /**
     * @return Collection<int, SteamaSyncSetting>
     */
    public function getSyncSettings(): Collection {
        return $this->syncSetting->newQuery()->get();
    }

    public function getSyncSettingsByActionName(string $actionName): SteamaSyncSetting {
        try {
            return $this->syncSetting->newQuery()->where('action_name', $actionName)->firstOrFail();
        } catch (\Exception $exception) {
            throw new ModelNotFoundException($exception->getMessage());
        }
    }
}
