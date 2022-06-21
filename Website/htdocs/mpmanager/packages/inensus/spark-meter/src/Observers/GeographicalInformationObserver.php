<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Models\SmCustomer;

class GeographicalInformationObserver
{
    private $customerService;
    private $smTableEncryption;
    private $person;
    private $smCustomer;

    public function __construct(
        CustomerService $customerService,
        SmTableEncryption $smTableEncryption,
        Person $person,
        SmCustomer $smCustomer
    ) {
        $this->customerService = $customerService;
        $this->smTableEncryption = $smTableEncryption;
        $this->person = $person;
        $this->smCustomer = $smCustomer;
    }

    public function updated(GeographicalInformation $geographicalInformation)
    {
        if ($geographicalInformation->owner_type === 'meter_parameter') {
            $meterParameterId = $geographicalInformation->owner_id;
            $customer = $this->person->newQuery()->with(['meters.tariff', 'meters.geo', 'meters.meter'])
                ->whereHas('meters', function ($q) use ($meterParameterId) {
                    return $q->where('id', $meterParameterId);
                })->first();
            $smCustomer = $this->smCustomer->newQuery()
                ->where('mpm_customer_id', $customer->id)->first();
            if ($smCustomer) {
                $address = $customer->addresses()->where('is_primary', 1)->first();
                $customerData = [
                    'id' => $smCustomer->customer_id,
                    'active' => true,
                    'meter_tariff_name' => $customer->meters[0]->tariff->name,
                    'name' => $customer->name . ' ' . $customer->surname,
                    'code' => strval($customer->id),
                    'phone_number' => $address->phone,
                    'coords' => $customer->meters[0]->geo->points,
                    'address' => $address->street
                ];

                $this->customerService->updateSparkCustomerInfo($customerData, $smCustomer->site_id);
                $smModelHash = $this->customerService->hashCustomerWithMeterSerial(
                    $customer->meters[0]->meter->serial_number,
                    $smCustomer->site_id
                );
                $smCustomer->update([
                    'hash' => $smModelHash
                ]);
            }
        }
    }
}
