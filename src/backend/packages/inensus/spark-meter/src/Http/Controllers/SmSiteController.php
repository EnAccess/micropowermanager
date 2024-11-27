<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\SparkMeter\Http\Requests\SmSiteRequest;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Services\SiteService;

class SmSiteController implements IBaseController {
    private $siteService;

    public function __construct(
        SiteService $siteService,
    ) {
        $this->siteService = $siteService;
    }

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

    public function count() {
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
