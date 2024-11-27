<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\UsageTypeService;
use Illuminate\Http\Request;

class UsageTypeController extends Controller {
    public function __construct(private UsageTypeService $usageTypeService) {}

    public function index(Request $request): ApiResource {
        return ApiResource::make($this->usageTypeService->getAll());
    }
}
