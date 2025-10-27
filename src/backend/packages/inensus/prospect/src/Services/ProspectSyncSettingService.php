<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Collection;
use Inensus\Prospect\Models\ProspectSyncAction;
use Inensus\Prospect\Models\ProspectSyncSetting;

class ProspectSyncSettingService {
    public function __construct(private ProspectSyncSetting $syncSetting, private ProspectSyncActionService $syncActionService) {}

    /**
     * Update sync settings.
     *
     * @param array<int, array<string, mixed>> $syncSettings
     *
     * @return Collection<int, ProspectSyncSetting>
     */
    public function updateSyncSettings(array $syncSettings) {
        foreach ($syncSettings as $setting) {
            $saved = $this->syncSetting->newQuery()->updateOrCreate(
                ['id' => $setting['id']],
                [
                    'action_name' => $setting['action_name'],
                    'sync_in_value_str' => $setting['sync_in_value_str'],
                    'sync_in_value_num' => $setting['sync_in_value_num'],
                    'max_attempts' => $setting['max_attempts'],
                ]
            );

            // Ensure there is a matching sync action; if missing, create one with next_sync scheduled
            $existingAction = $this->syncActionService->getSyncActionBySynSettingId($saved->id);
            if (!$existingAction instanceof ProspectSyncAction) {
                $interval = CarbonInterval::make($saved->sync_in_value_num.$saved->sync_in_value_str);
                $this->syncActionService->createSyncAction([
                    'sync_setting_id' => $saved->id,
                    'next_sync' => Carbon::now()->add($interval),
                ]);
            }
        }

        return $this->syncSetting->newQuery()->get();
    }

    /**
     * Get all sync settings.
     *
     * @return Collection<int, ProspectSyncSetting>
     */
    public function getSyncSettings() {
        return $this->syncSetting->newQuery()->get();
    }

    public function createDefaultSettings(): void {
        $dayInterval = CarbonInterval::make('1day');
        $now = Carbon::now();

        $installations = $this->syncSetting->newQuery()->where('action_name', 'Installations')->first();
        if (!$installations) {
            $installations = $this->syncSetting->newQuery()->create([
                'action_name' => 'Installations',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $this->syncActionService->createSyncAction([
                'sync_setting_id' => $installations->id,
                'next_sync' => $now->add($dayInterval),
            ]);
        }
    }
}
