<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\KelinSettingCollection;
use Inensus\KelinMeter\Http\Resources\KelinSettingResource;
use Inensus\KelinMeter\Services\KelinSettingService;

class KelinSettingController extends Controller {
    public function __construct(private KelinSettingService $settingService) {}

    public function index(): KelinSettingCollection {
        return new KelinSettingCollection(
            KelinSettingResource::collection($this->settingService->getSettings())
        );
    }
}
