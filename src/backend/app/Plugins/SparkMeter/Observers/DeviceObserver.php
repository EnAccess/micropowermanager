<?php

namespace App\Plugins\SparkMeter\Observers;

use App\Models\Device;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Services\CustomerService;

class DeviceObserver {
    public function __construct(private CustomerService $customerService, private SmCustomer $smCustomer) {}

    /**
     * A ThunderCloud customer is the meter itself, so when a SparkMeter meter
     * changes owner the customer mapping and ThunderCloud's customer details
     * follow the meter to its new owner.
     */
    public function updated(Device $device): void {
        if (!$device->wasChanged('person_id') || !$this->customerService->isSparkMeterDevice($device)) {
            return;
        }

        $smCustomer = $this->smCustomer->newQuery()->with('site')
            ->where('mpm_customer_id', $device->getOriginal('person_id'))->first();
        if (!$smCustomer) {
            return;
        }

        $smCustomer->update(['mpm_customer_id' => $device->person_id]);
        $this->customerService->pushCustomerToSpark($smCustomer, $device->person, $device);
    }
}
