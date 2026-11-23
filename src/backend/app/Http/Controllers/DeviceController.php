<?php

namespace App\Http\Controllers;

use App\Enums\VendingCapability;
use App\Events\NewLogEvent;
use App\Http\Requests\DeviceListRequest;
use App\Http\Requests\GenerateDeviceTokenRequest;
use App\Http\Requests\UpdateDeviceRequest;
use App\Http\Requests\UpdateDeviceVendingPluginRequest;
use App\Http\Resources\ApiResource;
use App\Http\Resources\DeviceCapabilitiesResource;
use App\Http\Resources\DeviceMappingResource;
use App\Http\Resources\TokenResource;
use App\Models\Device;
use App\Services\DeviceControlService;
use App\Services\DeviceService;
use Illuminate\Http\Request;

class DeviceController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private DeviceControlService $deviceControlService,
    ) {}

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

        return ApiResource::make($updatedDevice->load('person'));
    }

    /**
     * Verify manufacturer device mapping.
     *
     * Queries the manufacturer's device management API to check whether the
     * device is still mapped on the manufacturer side and persists the outcome
     * on the device as `mapped`, `not_mapped` or `unsupported`.
     */
    public function deviceInfo(Device $device): DeviceMappingResource {
        return DeviceMappingResource::make($this->deviceControlService->refreshManufacturerMapping($device));
    }

    /**
     * List the plugins a device can be vended by.
     *
     * Returns the device's current `vending_plugin_id` and the `options` it can be
     * moved to: its manufacturer plugin, plus every active plugin that vends devices
     * without being a manufacturer.
     */
    public function vendingPlugins(Device $device): ApiResource {
        return ApiResource::make([
            'vending_plugin_id' => $device->vending_plugin_id,
            'options' => $this->deviceControlService->vendingPluginOptions($device),
        ]);
    }

    /**
     * Change the plugin a device is vended by.
     *
     * `vending_plugin_id` must be one of the plugins listed for the device; `null`
     * moves the device back to its manufacturer plugin. The change is also written
     * to the audit log.
     */
    public function updateVendingPlugin(Device $device, UpdateDeviceVendingPluginRequest $request): ApiResource {
        $previousPluginId = $device->vending_plugin_id;
        $updatedDevice = $this->deviceControlService->updateVendingPlugin($device, $request->vendingPluginId());

        event(new NewLogEvent([
            'user_id' => auth('api')->user()->id,
            'affected' => $updatedDevice,
            'action' => "Device vending plugin changed from pluginId: $previousPluginId to pluginId: $updatedDevice->vending_plugin_id",
        ]));

        return ApiResource::make($updatedDevice);
    }

    /**
     * Read what the manufacturer lets MPM do with a device.
     *
     * Tells callers which tokens can be generated on demand, so each action is
     * offered only where the device's vending provider actually supports it.
     */
    public function capabilities(Device $device): DeviceCapabilitiesResource {
        return DeviceCapabilitiesResource::make($this->deviceControlService->capabilities($device));
    }

    /**
     * Generate a token without a customer payment.
     *
     * `type` selects what the token does, and defaults to `credit`:
     *
     * - `credit` issues credit straight from the manufacturer. The `amount` is read
     *   either as a currency amount or as the credit unit the device carries; the
     *   response reports the credit actually issued, which can differ because
     *   manufacturers round. Access rates and appliance installments are not settled.
     * - `unlock` releases a solar home system from pay-as-you-go for good.
     * - `reset` resets a solar home system, so a repossessed unit can be resold under
     *   a new payment plan. It does not change the device's owner or payment plan in MPM.
     * - `clear_credit` sets a meter's remaining credit to zero.
     *
     * Only the types listed in `token_types` of the capabilities endpoint are
     * available for a device. Every type except `credit` needs no `amount`. Every type
     * is recorded as an ad-hoc transaction, at a zero amount for those that carry no credit.
     */
    public function generateToken(Device $device, GenerateDeviceTokenRequest $request): TokenResource {
        $creatorId = auth('api')->user()->id;

        $type = $request->type();

        $token = match ($type) {
            VendingCapability::Credit => $this->deviceControlService->generateToken(
                $device,
                $request->float('amount'),
                $request->unit(),
                $creatorId,
            ),
            VendingCapability::Unlock => $this->deviceControlService->generateUnlockToken($device, $creatorId),
            VendingCapability::Reset, VendingCapability::ClearCredit => $this->deviceControlService->generateClearToken($device, $type, $creatorId),
        };

        return TokenResource::make($token);
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
