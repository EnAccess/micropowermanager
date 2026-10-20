<?php

namespace App\Plugins\SparkMeter\Observers;

use App\Models\Device;
use App\Models\Person\Person;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Services\CustomerService;

class PersonObserver {
    public function __construct(private CustomerService $customerService, private SmCustomer $smCustomer) {}

    public function updated(Person $person): void {
        $smCustomer = $this->smCustomer->newQuery()->with('site')
            ->where('mpm_customer_id', $person->id)->first();
        if (!$smCustomer) {
            return;
        }

        $device = $person->devices()->with('device')->get()->first(
            fn (Device $device): bool => $this->customerService->isSparkMeterDevice($device)
        );
        if (!$device) {
            return;
        }

        $this->customerService->pushCustomerToSpark($smCustomer, $person, $device);
    }
}
