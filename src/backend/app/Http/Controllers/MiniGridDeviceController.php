<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridDeviceService;
use Illuminate\Http\Request;

class MiniGridDeviceController extends Controller {
    public function __construct(
        private MiniGridDeviceService $miniGridDeviceService,
    ) {}

    public function index(Request $request, int $miniGridId): ApiResource {
        return ApiResource::make($this->miniGridDeviceService->getDevicesByMiniGridId($miniGridId));
    }
}
