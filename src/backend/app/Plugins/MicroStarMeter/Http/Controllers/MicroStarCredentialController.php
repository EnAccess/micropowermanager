<?php

namespace App\Plugins\MicroStarMeter\Http\Controllers;

use App\Plugins\MicroStarMeter\Http\Requests\MicroStarCredentialRequest;
use App\Plugins\MicroStarMeter\Http\Resources\MicroStarResource;
use App\Plugins\MicroStarMeter\Services\MicroStarCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Micro Star Meter', 'API endpoints for integrating with MicroStar meters')]
class MicroStarCredentialController extends Controller {
    public function __construct(private MicroStarCredentialService $credentialService) {}

    public function show(): MicroStarResource {
        return MicroStarResource::make($this->credentialService->getCredentials());
    }

    public function update(MicroStarCredentialRequest $request): MicroStarResource {
        $credentials = $this->credentialService->updateCredentials($request->only([
            'id',
            'api_url',
            'certificate_password',
        ]));

        return MicroStarResource::make($credentials);
    }
}
