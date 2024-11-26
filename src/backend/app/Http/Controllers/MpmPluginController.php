<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MpmPluginService;
use Illuminate\Http\Request;

class MpmPluginController extends Controller {
    public function __construct(private MpmPluginService $mpmPluginService) {}

    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->mpmPluginService->getAll($limit));
    }
}
