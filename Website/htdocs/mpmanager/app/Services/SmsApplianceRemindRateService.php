<?php

namespace App\Services;

use App\Models\AssetType;
use App\Models\SmsApplianceRemindRate;

class SmsApplianceRemindRateService
{
    private $smsApplianceRemindRate;
    private $applianceType;

    public function __construct(SmsApplianceRemindRate $smsApplianceRemindRate, AssetType $applianceType)
    {
        $this->smsApplianceRemindRate = $smsApplianceRemindRate;
        $this->applianceType = $applianceType;
    }
    public function getApplianceRemindRatesWithApplianceTypes()
    {
        return $this->applianceType->newQuery()->with(['smsReminderRate'])->get();
    }
    public function getApplianceRemindRates()
    {
        return $this->smsApplianceRemindRate->newQuery()->get();
    }
    public function updateApplianceRemindRate(SmsApplianceRemindRate $smsApplianceRemindRate, $data)
    {
        $smsApplianceRemindRate->update([
            'appliance_type_id' => $data['appliance_type_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate']

        ]);
        return $this->applianceType->newQuery()->with(['smsReminderRate'])->get();
    }
    public function createApplianceRemindRate($data)
    {
        $this->smsApplianceRemindRate->newQuery()->create([
            'appliance_type_id' => $data['appliance_type_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate']
        ]);
        return $this->applianceType->newQuery()->with(['smsReminderRate'])->get();
    }
}
