<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAgentUnassignedDevicesRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\DeviceService;
use Illuminate\Validation\ValidationException;

class AgentAvailableDeviceController extends Controller {
    public function __construct(
        private DeviceService $deviceService,
        private AgentService $agentService,
    ) {}

    public function index(ListAgentUnassignedDevicesRequest $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $applianceId = $request->integer('appliance_id');

        $isAssignedToAgent = $agent->assignedAppliance()
            ->where('appliance_id', $applianceId)
            ->exists();

        if (!$isAssignedToAgent) {
            throw ValidationException::withMessages(['appliance_id' => 'You are not assigned this appliance.']);
        }

        $deviceClass = ListAgentUnassignedDevicesRequest::SUPPORTED_TYPES[$request->string('type')->toString()];
        $devices = $this->deviceService->getUnassignedByAppliance($applianceId, $deviceClass);

        return ApiResource::make($devices);
    }
}
