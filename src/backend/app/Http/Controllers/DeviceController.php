<?php

namespace App\Http\Controllers;

use App\Events\NewLogEvent;
use App\Http\Requests\DeviceListRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\DeviceMappingResource;
use App\Models\Device;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller {
    public function __construct(private DeviceService $deviceService) {}

    /**
     * List devices.
     *
     * List of devices with optional filter parameters. Calling the endpoints without filter 
     * parameters returns all devices.
     *
     * The endpoint returns device owner and type-specific details.
     */
    public function index(DeviceListRequest $request): ApiResource {
        return ApiResource::make(
            $this->deviceService->getAll($request->integer('per_page', 15), $request->filters())
        );
    }

    /**
     * Update a device.
     *
     * Primarily used to assign a device to another customer.
     * The owner change is also written to the audit log.
     */
    public function update(Device $device, UpdateDeviceRequest $request): ApiResource {
        $creatorId = auth('api')->user()->id;
        $previousOwner = $device->person_id;
        $newOwner = $request->integer('person_id');
        $deviceData = $request->validated();
        $updatedDevice = $this->deviceService->update($device, $deviceData);
        event(new NewLogEvent([
            'user_id' => $creatorId,
            'affected' => $device,
            'action' => "Device owner changed from personId: $previousOwner to personId: $newOwner",
        ]));

        return ApiResource::make($updatedDevice);
    }

    /**
     * Verify manufacturer device mapping.
     *
     * Queries the manufacturer's device management API to check whether the
     * device is still mapped on the manufacturer side and persists the outcome
     * on the device as `mapped`, `not_mapped` or `unsupported`.
     */
    public function deviceInfo(Device $device): DeviceMappingResource {
        return DeviceMappingResource::make($this->deviceService->refreshManufacturerMapping($device));
    }

    /**
     * Update device locations.
     *
     * Accepts a list of `{serial_number, lat, lon}` items and moves each
     * device to the given coordinates. Changes are also written to the audit log.
     */
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
