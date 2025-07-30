<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Device;
use MPM\Device\DeviceService;

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
}
