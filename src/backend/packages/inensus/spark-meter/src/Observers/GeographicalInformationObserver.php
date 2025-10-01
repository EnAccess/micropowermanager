<?php

namespace Inensus\SparkMeter\Observers;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Services\PluginsService;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Services\CustomerService;

class GeographicalInformationObserver {
    public function __construct(
        private CustomerService $customerService,
        private Person $person,
        private SmCustomer $smCustomer,
        private PluginsService $pluginsService,
    ) {}

    public function updated(GeographicalInformation $geographicalInformation): void {
        if (!$this->pluginsService->isPluginActive(MpmPlugin::SPARK_METER)) {
            return;
        }

        if ($geographicalInformation->owner instanceof Address) {
            $address = $geographicalInformation->owner;

            if ($address->owner instanceof Device) {
                $device = $address->owner;

                if ($device->device instanceof Meter) {
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
    private function updateSparkMetaCustomerInformation(Device $device, GeographicalInformation $geographicalInformation): void {
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
