<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

#[Group('AgentApp', weight: 21)]
class AgentMeterController extends Controller {
    public function __construct(
        private MeterService $meterService,
    ) {}

    /**
     * List un-assigned meters for the field agent.
     */
    public function index(Request $request): ApiResource {
        $manufacturerId = $request->integer('manufacturer_id') ?: null;
        $meterTypeId = $request->integer('meter_type_id') ?: null;

        return ApiResource::make(
            $this->meterService->getAvailable($manufacturerId, $meterTypeId)
        );
    }
}
