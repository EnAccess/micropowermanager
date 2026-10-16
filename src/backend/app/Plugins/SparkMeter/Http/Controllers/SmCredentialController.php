<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmCredentialRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkMeterCredentialResource;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\CredentialService;
use App\Plugins\SparkMeter\Services\SiteService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter', 'API endpoints for integrating with SparkMeter meters')]
class SmCredentialController extends Controller {
    public function __construct(
        private CredentialService $credentialService,
        private SiteService $siteService,
    ) {}

    public function show(): SparkResource {
        return new SparkResource($this->credentialService->getCredentials());
    }

    public function update(SmCredentialRequest $request): SparkMeterCredentialResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'api_key',
            'api_secret',
            'thundercloud_url',
            'thundercloud_token',
        ]));

        // There's no separate "add site" step: every save checks whether the connection's one
        // site is registered yet.
        $this->siteService->discoverSite();

        return new SparkMeterCredentialResource($credentials);
    }
}
