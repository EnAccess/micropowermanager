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
     * Normalize sync unit string to CarbonInterval compatible format.
     * Maps adverbs like "hourly", "daily", "weekly" to their base units.
     */
    private function normalizeSyncUnit(string $unit): string {
        return match (strtolower($unit)) {
            'hourly' => 'hour',
            'daily' => 'day',
            'weekly' => 'week',
            'monthly' => 'month',
            'yearly' => 'year',
            default => $unit,
        };
    }

    /**
     * Update sync settings.
     *
     * @param array<int, array<string, mixed>> $syncSettings
     *
     * @return Collection<int, ProspectSyncSetting>
     */
    public function updateSyncSettings(array $syncSettings) {
        foreach ($syncSettings as $setting) {
            $normalizedUnit = $this->normalizeSyncUnit($setting['sync_in_value_str']);
            $interval = CarbonInterval::make($setting['sync_in_value_num'].$normalizedUnit);

            // Use updateOrCreate with action_name as the unique identifier
            $syncSetting = $this->syncSetting->newQuery()->updateOrCreate(
                ['action_name' => $setting['action_name']],
                [
                    'sync_in_value_str' => $setting['sync_in_value_str'],
                    'sync_in_value_num' => $setting['sync_in_value_num'],
                    'max_attempts' => $setting['max_attempts'],
                ]
            );

            $syncSettingAction = $this->syncActionService->getSyncActionBySynSettingId($syncSetting->id);
            if ($syncSettingAction instanceof ProspectSyncAction) {
                $lastSync = $syncSettingAction->last_sync ?? Carbon::now();
                $syncSettingAction->update([
                    'next_sync' => $lastSync->add($interval),
                ]);
            } else {
                $now = Carbon::now();
                $this->syncActionService->createSyncAction([
                    'sync_setting_id' => $syncSetting->id,
                    'next_sync' => $now->add($interval),
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

        $payments = $this->syncSetting->newQuery()->where('action_name', 'Payments')->first();
        if (!$payments) {
            $payments = $this->syncSetting->newQuery()->create([
                'action_name' => 'Payments',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $this->syncActionService->createSyncAction([
                'sync_setting_id' => $payments->id,
                'next_sync' => $now->add($dayInterval),
            ]);
        }

        $customers = $this->syncSetting->newQuery()->where('action_name', 'Customers')->first();
        if (!$customers) {
            $customers = $this->syncSetting->newQuery()->create([
                'action_name' => 'Customers',
                'sync_in_value_str' => 'day',
                'sync_in_value_num' => 1,
            ]);
            $this->syncActionService->createSyncAction([
                'sync_setting_id' => $customers->id,
                'next_sync' => $now->add($dayInterval),
            ]);
        }
    }
}
