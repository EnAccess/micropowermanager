<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\Person\Person;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Services\CustomerService;

class PersonObserver {
    private $customerService;
    private $smTableEncryption;
    private $person;
    private $smCustomer;

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

    public function updated(Person $person) {
        $smCustomer = $this->smCustomer->newQuery()->with('site')
            ->where('mpm_customer_id', $person->id)->first();

        if ($smCustomer) {
            $personId = $person->id;
            $customer = $this->person->newQuery()
                ->with(['meters.tariff', 'meters.geo', 'meters.meter', 'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                }])->where('id', $personId)->first();

            $siteId = $smCustomer->site->site_id;

            $customerData = [
                'id' => $smCustomer->customer_id,
                'active' => true,
                'meter_tariff_name' => $customer->meters[0]->tariff->name,
                'name' => $person->name.' '.$person->surname,
                'phone_number' => $customer->addresses[0]->phone,
                'coords' => $customer->meters[0]->geo->points,
                'address' => $customer->addresses[0]->street,
            ];

            $this->customerService->updateSparkCustomerInfo($customerData, $siteId);

            $smModelHash = $this->smTableEncryption->makeHash([
                $person->name.' '.$person->surname,
                $customer->addresses[0]->phone,
                $customer->credit_balance,
                $customer->meters[0]->tariff->name,
                $customer->meters[0]->meter->serial_number,
            ]);

            $smCustomer->update([
                'hash' => $smModelHash,
            ]);
        }
    }
}
