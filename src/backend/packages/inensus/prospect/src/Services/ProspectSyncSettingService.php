<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Inensus\Prospect\Models\ProspectSyncSetting;

class ProspectSyncSettingService {
    public function __construct(private ProspectSyncSetting $syncSetting, private ProspectSyncActionService $syncActionService) {}

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
            if (!$existingAction) {
                $interval = $this->makeInterval($saved->sync_in_value_str, (int) $saved->sync_in_value_num);
                $this->syncActionService->createSyncAction([
                    'sync_setting_id' => $saved->id,
                    'next_sync' => Carbon::now()->add($interval),
                ]);
            }
        }

        return $this->syncSetting->newQuery()->get();
    }

    public function getSyncSettings() {
        return $this->syncSetting->newQuery()->get();
    }

    private function makeInterval(string $valueStr, int $valueNum): CarbonInterval {
        $map = [
            'everyMinute' => CarbonInterval::minutes(1),
            'everyFifteenMinutes' => CarbonInterval::minutes(15),
            'everyHour' => CarbonInterval::hours(1),
            'daily' => CarbonInterval::days(1),
            'weekly' => CarbonInterval::weeks(1),
            'monthly' => CarbonInterval::months(1),
        ];

        if (isset($map[$valueStr])) {
            return $map[$valueStr];
        }

        // Fallback to CarbonInterval::make using canonical units
        $unit = strtolower($valueStr);

        return match ($unit) {
            'minute', 'minutes' => CarbonInterval::minutes(max(1, $valueNum)),
            'hour', 'hours' => CarbonInterval::hours(max(1, $valueNum)),
            'day', 'days' => CarbonInterval::days(max(1, $valueNum)),
            'week', 'weeks' => CarbonInterval::weeks(max(1, $valueNum)),
            'month', 'months' => CarbonInterval::months(max(1, $valueNum)),
            default => CarbonInterval::minutes(15),
        };
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

        // No separate Push seeding; extract and push are handled under Installations
    }
}
