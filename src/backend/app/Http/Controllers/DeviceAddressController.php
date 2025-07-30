<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;

class DeviceAddressController extends Controller {
    public function __construct(
        private DeviceAddressService $deviceAddressService,
        private DeviceService $deviceService,
    ) {}

    public function update(Request $request): ApiResource {
        $creatorId = auth('api')->user()->id;
        $devices = $request->all();
        foreach ($devices as $deviceData) {
            $serialNumber = $deviceData['serial_number'];
            $device = $this->deviceService->getBySerialNumber($serialNumber);
            $previousDataOfDevice = json_encode($device->toArray());
            $deviceAddress = $this->deviceAddressService->getAddressByDevice($device);
            $this->deviceAddressService->updateDeviceAddress($deviceAddress, $deviceData);
            $updatedDevice = $this->deviceService->getBySerialNumber($serialNumber);
            $updatedDataOfDevice = json_encode($updatedDevice->toArray());
            event(new NewLogEvent([
                'user_id' => $creatorId,
                'affected' => $device,
                'action' => "Device infos updated from: $previousDataOfDevice to $updatedDataOfDevice",
            ]));
        }

        return ApiResource::make($devices);
    }
}
