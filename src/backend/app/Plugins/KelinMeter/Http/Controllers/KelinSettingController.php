<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\KelinSettingCollection;
use App\Plugins\KelinMeter\Http\Resources\KelinSettingResource;
use App\Plugins\KelinMeter\Services\KelinSettingService;
use Illuminate\Routing\Controller;

class KelinSettingController extends Controller {
    public function __construct(private KelinSettingService $settingService) {}

    public function index(): KelinSettingCollection {
        return new KelinSettingCollection(
            KelinSettingResource::collection($this->settingService->getSettings())
        );
    }
}
