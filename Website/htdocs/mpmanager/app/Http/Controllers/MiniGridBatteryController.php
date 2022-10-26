<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridBatteryService;
use App\Services\MiniGridService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MiniGridBatteryController extends Controller
{
    public function __construct(private MiniGridBatteryService $miniGridBatteryService)
    {
    }

    /**
     * Battery details for Mini-Grid
     *
     * @urlParam miniGridId int required
     * @urlParam limit int Default 50
     *
     * @param  Request $request
     * @param  $id
     * @return ApiResource
     */
    public function show($miniGridId, Request $request): ApiResource
    {
        $limit = $request->get('limit');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return new ApiResource($this->miniGridBatteryService->getById($miniGridId, $startDate, $endDate, $limit));
    }
}
