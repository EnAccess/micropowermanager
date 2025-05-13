<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\Device;
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
        if (!$this->pluginsService->isPluginActive(MpmPlugin::SPARK_METER)) {
            return;
        }

        if ($geographicalInformation->owner_type === 'address') {
            $address = $geographicalInformation->owner;

            if ($address && $address->owner_type === 'device') {
                $device = $address->owner;

                if ($device && $device->device_type === 'meter') {
                    $this->updateSparkMetaCustomerInformation($device, $geographicalInformation);
                }
            }
        }
    }

    /**
     * Update Spark meter customer information.
     *
     * @param Device                  $device
     * @param GeographicalInformation $geographicalInformation
     *
     * @return void
     */
    private function updateSparkMetaCustomerInformation(Device $device, GeographicalInformation $geographicalInformation) {
        $meter = $device->device;

        $customer = $this->person->newQuery()
            ->with(['devices.device.tariff', 'devices.address.geo'])
            ->whereHas('devices', function ($q) use ($device) {
                return $q->where('id', $device->id);
            })->first();

        if (!$customer) {
            return;
        }

        $smCustomer = $this->smCustomer->newQuery()
            ->where('mpm_customer_id', $customer->id)
            ->first();

        if (!$smCustomer) {
            return;
        }

        $primaryAddress = $customer->addresses()->where('is_primary', 1)->first();
        $customerData = [
            'id' => $smCustomer->customer_id,
            'active' => true,
            'meter_tariff_name' => $customer->devices[0]->device->tariff->name,
            'name' => $customer->name.' '.$customer->surname,
            'code' => strval($customer->id),
            'phone_number' => $primaryAddress->phone,
            'coords' => $geographicalInformation->points,
            'address' => $primaryAddress->street,
        ];

        $this->customerService->updateSparkCustomerInfo($customerData, $smCustomer->site_id);
        $smModelHash = $this->customerService->hashCustomerWithMeterSerial(
            $meter->serial_number,
            $smCustomer->site_id
        );

        $smCustomer->update([
            'hash' => $smModelHash,
        ]);
    }
}
