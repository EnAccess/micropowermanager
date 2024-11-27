<?php

namespace Inensus\KelinMeter\Observers;

use App\Models\Person\Person;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Services\KelinCustomerService;

class PersonObserver {
    private $customerService;
    private $apiHelpers;
    private $person;
    private $kelinCustomer;

    public function __construct(
        KelinCustomerService $customerService,
        ApiHelpers $apiHelpers,
        Person $person,
        KelinCustomer $kelinCustomer,
    ) {
        $this->customerService = $customerService;
        $this->apiHelpers = $apiHelpers;
        $this->person = $person;
        $this->kelinCustomer = $kelinCustomer;
    }

    public function updated(Person $person) {
        $kelinCustomer = $this->kelinCustomer->newQuery()->where('mpm_customer_id', $person->id)->first();

        if ($kelinCustomer) {
            $personId = $person->id;
            $customer = $this->person->newQuery()
                ->with([
                    'addresses' => function ($q) {
                        return $q->where('is_primary', 1);
                    },
                ])->where('id', $personId)->first();

            /*      $customerData = [
                      'id' => $smCustomer->customer_id,
                      'active' => true,
                      'meter_tariff_name' => $customer->meters[0]->tariff->name,
                      'name' => $person->name . ' ' . $person->surname,
                      'code' => strval($person->id),
                      'phone_number' => $customer->addresses[0]->phone,
                      'coords' => $customer->meters[0]->geo->points,
                      'address' => $customer->addresses[0]->street
                  ];*/

            // $this->customerService->updateSparkCustomerInfo($customerData, $siteId);

            $kelinCustomerHash = $this->apiHelpers->makeHash([
                $kelinCustomer->customer_no,
                $kelinCustomer->address,
                $customer->addresses[0]->phone,
                $person->name.' '.$person->surname,
            ]);
            $kelinCustomer->update([
                'hash' => $kelinCustomerHash,
            ]);
        }
    }
}
