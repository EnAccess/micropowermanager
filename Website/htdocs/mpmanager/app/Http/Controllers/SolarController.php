<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolarCreateRequest;
use App\Http\Resources\ApiResource;
use App\Services\ISolarService;

class SolarController extends Controller
{
    /**
     * @var ISolarService
     */
    private $solarService;

    public function __construct(ISolarService $solarService)
    {
        $this->solarService = $solarService;
    }

    public function index(): ApiResource
    {
        $solarReadings = $this->solarService->list();
        return new ApiResource($solarReadings);
    }

    public function listByMiniGrid($miniGridId): ApiResource
    {
        echo "miniGridId " . $miniGridId;
        $solarReadings = $this->solarService->lisByMiniGrid($miniGridId);

        return new ApiResource($solarReadings);
    }

    public function showByMiniGrid($miniGridId)
    {
        if ($reading = $this->solarService->showByMiniGrid($miniGridId)) {
            return new ApiResource($reading);
        }

        return response()->setStatusCode(404)->json(['data' => 'Nothing found']);
    }

    public function store(SolarCreateRequest $request): ApiResource
    {
        //unused parameter $request is needed for validation

        $solar = $this->solarService->create();

        return new ApiResource($solar);
    }
}
