
<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenPaygoDeviceConfigurationRequest;
use App\Models\Device;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use Illuminate\Http\JsonResponse;

class OpenPaygoDeviceConfigurationController extends Controller
{
    public function __construct(
        private readonly OpenPaygoDeviceConfigurationService $configurationService,
    ) {
    }

    /**
     * Create or update OpenPAYGO provisioning for an existing device.
          */
    public function store(
        OpenPaygoDeviceConfigurationRequest $request,
        Device $device,
    ): JsonResponse {
        $data = $request->validated();

        $configuration = $this->configurationService->saveForDevice(
            device: $device,
            secretKeyHex: $data['secretKeyHex'],
            startingCode: (int) $data['startingCode'],
            nextCounter: (int) $data['nextCounter'],
            timeDivider: isset($data['timeDivider'])
                ? (int) $data['timeDivider']
                : null,
        );

        // Do not return the encrypted secret or the plaintext secret key.
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
