<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\SmsApplianceRemindRate;

class SmsApplianceRemindRateService
{
    public function __construct(
        private SmsApplianceRemindRate $smsApplianceRemindRate,
        private Asset $appliance
    ) {

    }

    public function getApplianceRemindRatesWithAppliances()
    {
        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }

    public function getApplianceRemindRates()
    {
        return $this->smsApplianceRemindRate->newQuery()->get();
    }

    public function updateApplianceRemindRate(SmsApplianceRemindRate $smsApplianceRemindRate, $data)
    {
        $smsApplianceRemindRate->update([
            'appliance_id' => $data['appliance_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate']

        ]);
        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }

    public function createApplianceRemindRate($data)
    {
        $this->smsApplianceRemindRate->newQuery()->create([
            'appliance_id' => $data['appliance_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate']
        ]);
        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }
}
