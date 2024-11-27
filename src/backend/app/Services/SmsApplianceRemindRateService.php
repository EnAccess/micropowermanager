<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\SmsApplianceRemindRate;
use Illuminate\Support\Collection;

class SmsApplianceRemindRateService {
    public function __construct(
        private SmsApplianceRemindRate $smsApplianceRemindRate,
        private Asset $appliance,
    ) {}

    public function getApplianceRemindRatesWithAppliances(): Collection {
        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }

    public function getApplianceRemindRates(): Collection {
        return $this->smsApplianceRemindRate->newQuery()->get();
    }

    public function updateApplianceRemindRate(SmsApplianceRemindRate $smsApplianceRemindRate, $data): Asset {
        $smsApplianceRemindRate->update([
            'appliance_id' => $data['appliance_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate'],
        ]);

        /** @var Asset $result */
        $result = $this->appliance->newQuery()->with(['smsReminderRate'])->get();

        return $result;
    }

    public function createApplianceRemindRate($data): Collection {
        $this->smsApplianceRemindRate->newQuery()->create([
            'appliance_id' => $data['appliance_type_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate'],
        ]);

        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }
}
