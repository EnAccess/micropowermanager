<?php

namespace App\Http\Controllers;

use App\Http\Resources\MpmPluginResource;
use App\Services\MpmPluginCatalog;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MpmPluginController extends Controller {
    public function __construct(private MpmPluginCatalog $mpmPluginCatalog) {}

    /**
     * List every plugin shipped with MicroPowerManager.
     */
    public function index(): AnonymousResourceCollection {
        return MpmPluginResource::collection(array_values($this->mpmPluginCatalog->all()));
    }
}
