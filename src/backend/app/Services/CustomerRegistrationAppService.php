<?php

namespace App\Services;

use App\Events\AccessRatePaymentInitialize;
use App\Http\Requests\AndroidAppRequest;
use App\Models\Meter\Meter;
use App\Models\Person\Person;

class CustomerRegistrationAppService {
    public function __construct(
        private PersonService $personService,
        private MeterService $meterService,
        private DeviceService $deviceService,
        private MeterDeviceService $meterDeviceService,
        private AddressesService $addressService,
        private GeographicalInformationService $geographicalInformationService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
    ) {}

    public function createCustomer(AndroidAppRequest $request): Person {
        $serialNumber = $request->input('serial_number');
        $meter = $this->meterService->getBySerialNumber($serialNumber);
        $phone = $request->input('phone');
        if ($meter instanceof Meter) {
            throw new \Exception('Meter already exists');
        }

        $person = $this->personService->getByPhoneNumber($phone);
        $manufacturerId = $request->input('manufacturer');
        $meterTypeId = $request->input('meter_type');
        $connectionTypeId = $request->input('connection_type_id');
        $connectionGroupId = $request->input('connection_group_id');
        $tariffId = $request->input('tariff_id');
        $cityId = $request->input('city_id');
        $geoPoints = $request->input('geo_points');
        if (!$person instanceof Person) {
            $request->attributes->add(['is_customer' => 1]);
            $person = $this->personService->createFromRequest($request);
        }
        $meterData = [
            'serial_number' => $serialNumber,
            'connection_group_id' => $connectionGroupId,
            'manufacturer_id' => $manufacturerId,
            'meter_type_id' => $meterTypeId,
            'connection_type_id' => $connectionTypeId,
            'tariff_id' => $tariffId,
            'in_use' => 1,
        ];
        $meter = $this->meterService->create($meterData);
        $device = $this->deviceService->make([
            'person_id' => $person->id,
            'device_serial' => $meter->serial_number,
        ]);
        $this->meterDeviceService->setAssigned($device);
        $this->meterDeviceService->setAssignee($meter);
        $this->meterDeviceService->assign();
        $this->deviceService->save($device);
        $addressData = [
            'city_id' => $cityId ?? 1,
        ];
        $address = $this->addressService->make($addressData);
        $this->addressService->assignAddressToOwner($person, $address);
        $geographicalInformation = $this->geographicalInformationService->make([
            'points' => $geoPoints,
        ]);
        $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
        $this->addressGeographicalInformationService->setAssignee($address);
        $this->addressGeographicalInformationService->assign();
        $this->geographicalInformationService->save($geographicalInformation);
        // initializes a new Access Rate Payment for the next Period
        event(new AccessRatePaymentInitialize($meter));

        return $person;
    }
}
