<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmSiteRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Services\SiteService;
use Illuminate\Http\Request;

class SmSiteController implements IBaseController {
    public function __construct(private SiteService $siteService) {}

    public function index(Request $request): SparkResource {
        $customers = $this->siteService->getSmSites($request);

        return new SparkResource($customers);
    }

    public function sync(): SparkResource {
        return new SparkResource($this->siteService->sync());
    }

    public function checkSync(): SparkResource {
        return new SparkResource($this->siteService->syncCheck());
    }

    public function count(): int {
        return $this->siteService->getSmSitesCount();
    }

    public function location(): SparkResource {
        return new SparkResource($this->siteService->checkLocationAvailability());
    }

    public function update(SmSite $site, SmSiteRequest $request): SparkResource {
        return new SparkResource($this->siteService->update($site->id, $request->only([
            'id',
            'thundercloud_url',
            'thundercloud_token',
        ])));
    }
}
