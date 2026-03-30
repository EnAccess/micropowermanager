<?php

namespace App\Plugins\SteamaMeter\Observers;

use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Models\SteamaCustomer;
use App\Plugins\SteamaMeter\Services\SteamaCustomerService;

class PersonObserver {
    public function __construct(
        private SteamaCustomerService $stmCustomerService,
        private Person $person,
        private SteamaCustomer $stmCustomer,
    ) {}

    public function updated(Person $person): void {
        $stmCustomer = $this->stmCustomer->newQuery()->with('site')->where('mpm_customer_id', $person->id)->first();

        if ($stmCustomer) {
            $personId = $person->id;
            $customer = $this->person->newQuery()->with([
                'devices.device.tariff',
                'devices.device.geo',
                'devices.device.meter',
                'addresses' => fn ($q) => $q->where('is_primary', 1),
            ])->where('id', $personId)->first();

            $customerData = [
                'id' => $stmCustomer->customer_id,
                'first_name' => $person->name,
                'last_name' => $person->surname,
                'telephone' => $customer->addresses[0]->phone,
            ];
            $this->stmCustomerService->updateSteamaCustomerInfo($stmCustomer, $customerData);
        }
    }
}
