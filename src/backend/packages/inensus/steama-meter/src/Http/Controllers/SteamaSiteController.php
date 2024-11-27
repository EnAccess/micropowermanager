<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSiteService;

class SteamaSiteController extends Controller implements IBaseController {
    private $siteService;

    public function __construct(SteamaSiteService $siteService) {
        $this->siteService = $siteService;
    }

    public function index(Request $request): SteamaResource {
        $sites = $this->siteService->getSites($request);

        return new SteamaResource($sites);
    }

    public function sync(): SteamaResource {
        return new SteamaResource($this->siteService->sync());
    }

    public function checkSync(): SteamaResource {
        return new SteamaResource($this->siteService->syncCheck());
    }

    public function count() {
        return $this->siteService->getSitesCount();
    }

    public function location(): SteamaResource {
        return new SteamaResource($this->siteService->checkLocationAvailability());
    }
}
