<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MeterService;
use Illuminate\Http\Request;

/**
 * @group   Agent-Meters
 */
class AgentMeterController extends Controller {
    public function __construct(
        private MeterService $meterService,
    ) {}

    /**
     * List un-assigned meters for the field agent.
     *
     * @queryParam manufacturer_id int
     * @queryParam meter_type_id int
     */
    public function index(Request $request): ApiResource {
        $manufacturerId = $request->integer('manufacturer_id') ?: null;
        $meterTypeId = $request->integer('meter_type_id') ?: null;

        return ApiResource::make(
            $this->meterService->getAvailable($manufacturerId, $meterTypeId)
        );
    }
}
