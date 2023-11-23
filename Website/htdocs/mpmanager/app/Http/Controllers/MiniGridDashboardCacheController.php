<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridDashboardCacheDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MiniGridDashboardCacheController extends Controller
{
    public function __construct(private MiniGridDashboardCacheDataService $miniGridDashboardCacheDataService)
    {
    }

    public function index()
    {
        return ApiResource::make($this->miniGridDashboardCacheDataService->getData());
    }

    public function show($miniGridId)
    {
        return ApiResource::make($this->miniGridDashboardCacheDataService->getDataById($miniGridId));
    }

    public function update(Request $request)
    {
        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        if ($toDate && $fromDate) {
            $this->miniGridDashboardCacheDataService->setData([$fromDate, $toDate]);
        } else {

            $this->miniGridDashboardCacheDataService->setData();
        }
        return ['data' => $this->miniGridDashboardCacheDataService->getData()];
    }
}
