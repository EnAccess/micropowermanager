<?php

namespace Inensus\Prospect\Services;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Inensus\Prospect\Models\ProspectSyncAction;

class ProspectSyncActionService {
    public function __construct(private ProspectSyncAction $syncAction) {}

    public function createSyncAction(array $syncAction) {
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
        $interval = $this->makeInterval($syncSetting->sync_in_value_str, (int) $syncSetting->sync_in_value_num);

        return $syncAction->update([
            'attempts' => 0,
            'last_sync' => Carbon::now(),
            'next_sync' => Carbon::now()->add($interval),
        ]);
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
        $canonical = match ($unit) {
            'minute', 'minutes' => CarbonInterval::minutes(max(1, $valueNum)),
            'hour', 'hours' => CarbonInterval::hours(max(1, $valueNum)),
            'day', 'days' => CarbonInterval::days(max(1, $valueNum)),
            'week', 'weeks' => CarbonInterval::weeks(max(1, $valueNum)),
            'month', 'months' => CarbonInterval::months(max(1, $valueNum)),
            default => CarbonInterval::minutes(15),
        };

        return $canonical;
    }
}


