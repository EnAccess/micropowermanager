<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Http\Request;
use MPM\Device\MiniGridDeviceService;

class MiniGridDeviceController extends Controller {
    public function __construct(
        private MiniGridDeviceService $miniGridDeviceService,
    ) {}

    public function index(Request $request, int $miniGridId): ApiResource {
        return ApiResource::make($this->miniGridDeviceService->getDevicesByMiniGridId($miniGridId));
    }
}
