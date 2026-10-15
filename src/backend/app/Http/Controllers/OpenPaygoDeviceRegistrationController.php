<?php

namespace App\Http\Controllers;

use App\Http\Requests\OpenPaygoDeviceRegistrationRequest;
use App\Models\Manufacturer;
use App\Models\SolarHomeSystem;
use App\Plugins\OpenPaygo\Services\OpenPaygoDeviceConfigurationService;
use App\Services\DeviceService;
use App\Services\SolarHomeSystemDeviceService;
use App\Services\SolarHomeSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OpenPaygoDeviceRegistrationController extends Controller {
    public function __construct(
        private readonly DeviceService $deviceService,
        private readonly SolarHomeSystemService $solarHomeSystemService,
        private readonly SolarHomeSystemDeviceService $deviceAssignmentService,
        private readonly OpenPaygoDeviceConfigurationService $configurationService,
    ) {}

    public function manufacturers(): JsonResponse {
        $manufacturers = Manufacturer::query()
            ->where('api_name', 'OpenPaygoApi')
            ->where('type', 'shs')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'data' => $manufacturers,
        ]);
    }

    public function store(OpenPaygoDeviceRegistrationRequest $request): JsonResponse {
        $data = $request->validated();

        $manufacturer = Manufacturer::query()->findOrFail($data['manufacturer_id']);

        $solarHomeSystem = DB::connection('tenant')->transaction(function () use ($data, $manufacturer): SolarHomeSystem {
            $device = $this->deviceService->make([
                'device_serial' => $data['serial_number'],
                'person_id' => $data['person_id'],
            ]);
            $solarHomeSystem = $this->solarHomeSystemService->create([
                'serial_number' => $data['serial_number'],
                'manufacturer_id' => $manufacturer->getKey(),
                'appliance_id' => $data['appliance_id'],
            ]);

            $this->deviceAssignmentService->setAssigned($device);
            $this->deviceAssignmentService->setAssignee($solarHomeSystem);
            $this->deviceAssignmentService->assign();
            $this->deviceService->save($device);

            $this->configurationService->saveForDevice(
                device: $device,
                secretKeyHex: $data['secretKeyHex'],
                startingCode: (int) $data['startingCode'],
                nextCounter: (int) $data['nextCounter'],
                timeDivider: (int) $data['timeDivider'],
            );

            return $solarHomeSystem->load(['manufacturer', 'appliance', 'device.person']);
        });

        return response()->json([
            'data' => $solarHomeSystem,
            'message' => 'OpenPAYGO device registered and configured successfully.',
        ], 201);
    }
}
