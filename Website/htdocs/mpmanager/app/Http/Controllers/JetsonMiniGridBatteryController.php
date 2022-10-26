<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridBatteryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JetsonMiniGridBatteryController extends Controller
{
    public function __construct(private MiniGridBatteryService $miniGridBatteryService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return  ApiResource::make($this->miniGridBatteryService->getForJetsonById($miniGridId, $startDate, $endDate));
    }
}
