<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenPaygoDeviceConfigurationRequest;
use App\Models\Device;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoDeviceConfigurationException;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use Illuminate\Http\JsonResponse;

class OpenPaygoDeviceConfigurationController extends Controller {
    public function __construct(
        private readonly OpenPaygoDeviceConfigurationService $configurationService,
    ) {}

    public function store(
        OpenPaygoDeviceConfigurationRequest $request,
        Device $device,
    ): JsonResponse {
        $data = $request->validated();

        /** @var Meter|SolarHomeSystem|EBike|null $deviceUnit */
        $deviceUnit = $device->device()->first();
        if (!$deviceUnit instanceof SolarHomeSystem && !$deviceUnit instanceof EBike
            || $deviceUnit->manufacturer?->api_name !== 'OpenPaygoApi') {
            throw new OpenPaygoDeviceConfigurationException('Select an OpenPAYGO solar home system or e-bike before configuring provisioning.');
        }

        $configuration = $this->configurationService->saveForDevice(
            device: $device,
            secretKeyHex: $data['secretKeyHex'],
            startingCode: (int) $data['startingCode'],
            nextCounter: isset($data['nextCounter']) ? (int) $data['nextCounter'] : null,
            timeDivider: isset($data['timeDivider'])
                ? (int) $data['timeDivider']
                : null,
        );

        return response()->json([
            'data' => [
                'device_id' => $device->getKey(),
                'configured' => true,
                'configuration_id' => $configuration->getKey(),
            ],
            'message' => 'OpenPAYGO device configuration saved successfully.',
        ], $configuration->wasRecentlyCreated ? 201 : 200);
    }
}
