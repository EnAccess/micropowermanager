<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\Person\Person;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Services\CustomerService;

class PersonObserver {
    private CustomerService $customerService;
    private SmTableEncryption $smTableEncryption;
    private Person $person;
    private SmCustomer $smCustomer;

    public function __construct(
        CustomerService $customerService,
        SmTableEncryption $smTableEncryption,
        Person $person,
        SmCustomer $smCustomer,
    ) {
        $this->customerService = $customerService;
        $this->smTableEncryption = $smTableEncryption;
        $this->person = $person;
        $this->smCustomer = $smCustomer;
    }

    public function updated(Person $person): void {
        $smCustomer = $this->smCustomer->newQuery()->with('site')
            ->where('mpm_customer_id', $person->id)->first();

        if ($smCustomer) {
            $personId = $person->id;
            $customer = $this->person->newQuery()
                ->with(['devices.device.tariff', 'devices.device.geo', 'devices.device.meter', 'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                }])->where('id', $personId)->first();

            $siteId = $smCustomer->site->site_id;

            $customerData = [
                'id' => $smCustomer->customer_id,
                'active' => true,
                'meter_tariff_name' => $customer->devices[0]->device->tariff->name,
                'name' => $person->name.' '.$person->surname,
                'phone_number' => $customer->addresses[0]->phone,
                'coords' => $customer->devices[0]->address->geo->points,
                'address' => $customer->addresses[0]->street,
            ];

            $this->customerService->updateSparkCustomerInfo($customerData, $siteId);

            $smModelHash = $this->smTableEncryption->makeHash([
                $person->name.' '.$person->surname,
                $customer->addresses[0]->phone,
                $smCustomer->credit_balance,
                $customer->devices[0]->device->tariff->name,
                $customer->devices[0]->device->serial_number,
            ]);

            $smCustomer->update([
                'hash' => $smModelHash,
            ]);
        }
    }
}
