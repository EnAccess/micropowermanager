<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\GeographicalInformation;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Services\PluginsService;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Services\CustomerService;

class GeographicalInformationObserver {
    public function __construct(
        private CustomerService $customerService,
        private SmTableEncryption $smTableEncryption,
        private Person $person,
        private SmCustomer $smCustomer,
        private PluginsService $pluginsService,
    ) {}

    public function updated(GeographicalInformation $geographicalInformation) {
        if ($this->pluginsService->isPluginActive(MpmPlugin::SPARK_METER) && $geographicalInformation->owner_type === 'meter_parameter') {
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
                    'name' => $customer->name.' '.$customer->surname,
                    'code' => strval($customer->id),
                    'phone_number' => $address->phone,
                    'coords' => $customer->meters[0]->geo->points,
                    'address' => $address->street,
                ];

                $this->customerService->updateSparkCustomerInfo($customerData, $smCustomer->site_id);
                $smModelHash = $this->customerService->hashCustomerWithMeterSerial(
                    $customer->meters[0]->meter->serial_number,
                    $smCustomer->site_id
                );
                $smCustomer->update([
                    'hash' => $smModelHash,
                ]);
            }
        }
    }
}
