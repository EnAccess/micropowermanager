<?php

namespace App\Http\Controllers;

use App\Enums\MpmPlugin;
use App\Http\Resources\MpmPluginResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MpmPluginController extends Controller {
    /**
     * List every plugin shipped with MicroPowerManager.
     */
    public function index(): AnonymousResourceCollection {
        return MpmPluginResource::collection(MpmPlugin::cases());
    }
}
