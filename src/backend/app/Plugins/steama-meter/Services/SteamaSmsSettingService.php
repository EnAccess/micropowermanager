<?php

namespace Inensus\SteamaMeter\Services;

use Illuminate\Database\Eloquent\Collection;
use Inensus\SteamaMeter\Models\SteamaSetting;
use Inensus\SteamaMeter\Models\SteamaSmsSetting;

class SteamaSmsSettingService {
    public function __construct(
        private SteamaSmsSetting $smsSetting,
        private SteamaSetting $setting,
    ) {}

    public function createDefaultSettings(): void {
        $smsTransaction = $this->smsSetting->newQuery()->where('state', 'Transactions')->first();
        if (!$smsTransaction) {
            $smsSetting = $this->setting->newQuery()->make();
            $smsTransaction = $this->smsSetting->newQuery()->create([
                'state' => 'Transactions',
                'not_send_elder_than_mins' => 30,
            ]);
            $smsSetting->setting()->associate($smsTransaction);
            $smsSetting->save();
        }
        $smsLowBalanceWarning = $this->smsSetting->newQuery()->where('state', 'Low Balance Warning')->first();
        if (!$smsLowBalanceWarning) {
            $balanceSetting = $this->setting->newQuery()->make();
            $smsLowBalanceWarning = $this->smsSetting->newQuery()->create([
                'state' => 'Low Balance Warning',
                'not_send_elder_than_mins' => 60,
            ]);
            $balanceSetting->setting()->associate($smsLowBalanceWarning);
            $balanceSetting->save();
        }
    }

    /**
     * @param array<int, SteamaSmsSetting> $smsSettings
     *
     * @return Collection<int, SteamaSmsSetting>
     */
    public function updateSmsSettings(array $smsSettings): Collection {
        foreach ($smsSettings as $setting) {
            $smsSetting = $this->smsSetting->newQuery()->find($setting['id']);
            if ($smsSetting) {
                $smsSetting->update([
                    'not_send_elder_than_mins' => $setting['not_send_elder_than_mins'],
                    'enabled' => $setting['enabled'],
                ]);
            }
        }

        return $this->smsSetting->newQuery()->get();
    }

    /**
     * @return Collection<int, SteamaSmsSetting>
     */
    public function getSmsSettings(): Collection {
        return $this->smsSetting->newQuery()->get();
    }
}
