<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller {
    public function __construct(private DeviceService $deviceService) {}

    public function index(): ApiResource {
        return ApiResource::make($this->deviceService->getAll());
    }

    public function update(Device $device, UpdateDeviceRequest $request): ApiResource {
        $creatorId = auth('api')->user()->id;
        $previousOwner = $device->person_id;
        $newOwner = $request->input('person_id');
        $deviceData = $request->validated();
        $updatedDevice = $this->deviceService->update($device, $deviceData);
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $device,
            'action' => "Device owner changed from personId: $previousOwner to personId: $newOwner",
        ]));

        return ApiResource::make($updatedDevice);
    }

    public function updateGeoInformation(Request $request): ApiResource {
        $creatorId = auth('api')->user()->id;
        $devices = $request->all();
        foreach ($devices as $deviceData) {
            $serialNumber = $deviceData['serial_number'];
            $device = $this->deviceService->getBySerialNumber($serialNumber);
            $previousDataOfDevice = json_encode($device->toArray());
            $this->deviceService->updateGeoInformation($device, $deviceData);
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
