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

    /**
     * @return Collection<int, Asset>
     */
    public function getApplianceRemindRatesWithAppliances(): Collection {
        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }

    /**
     * @return Collection<int, SmsApplianceRemindRate>
     */
    public function getApplianceRemindRates(): Collection {
        return $this->smsApplianceRemindRate->newQuery()->get();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return Asset|Collection<int, Asset>
     */
    public function updateApplianceRemindRate(SmsApplianceRemindRate $smsApplianceRemindRate, array $data): Asset|Collection {
        $smsApplianceRemindRate->update([
            'appliance_id' => $data['appliance_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate'],
        ]);

        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return Collection<int, Asset>
     */
    public function createApplianceRemindRate(array $data): Collection {
        $this->smsApplianceRemindRate->newQuery()->create([
            'appliance_id' => $data['appliance_type_id'],
            'overdue_remind_rate' => $data['overdue_remind_rate'],
            'remind_rate' => $data['remind_rate'],
        ]);

        return $this->appliance->newQuery()->with(['smsReminderRate'])->get();
    }
}
