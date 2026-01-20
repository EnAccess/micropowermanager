<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSiteService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaSiteController extends Controller implements IBaseController {
    public function __construct(private SteamaSiteService $siteService) {}

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

    public function count(): int {
        return $this->siteService->getSitesCount();
    }

    public function location(): SteamaResource {
        return new SteamaResource($this->siteService->checkLocationAvailability());
    }
}
