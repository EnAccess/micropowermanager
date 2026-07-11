<?php

namespace App\Plugins\Prospect\Http\Controllers;

use App\Models\MpmPlugin;
use App\Plugins\Prospect\Http\Resources\ProspectResource;
use App\Plugins\Prospect\Services\ProspectCredentialService;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

#[Group('Plugins / Prospect', 'API endpoints for integrating with the Prospect platform')]
class ProspectCredentialController extends Controller {
    public function __construct(
        private ProspectCredentialService $credentialService,
        private RegistrationTailService $registrationTailService,
        private MpmPluginService $mpmPluginService,
    ) {}

    public function show(): JsonResource {
        $credentials = $this->credentialService->getCredentials();
        if ($credentials === null) {
            return ProspectResource::collection([]);
        }

        return ProspectResource::collection($credentials);
    }

    public function update(Request $request): JsonResource {
        $request->validate([
            '*.id' => ['nullable', 'integer'],
            '*.api_url' => ['required', 'string'],
            '*.api_token' => ['required', 'string', 'min:3'],
        ]);

        $credentials = $this->credentialService->updateCredentials($request->all());

        // Mark Prospect step as adjusted in Registration Tail (credentials fully provided)
        try {
            $mpmPlugin = $this->mpmPluginService->getById(MpmPlugin::PROSPECT);
            $this->registrationTailService->adjustStep($mpmPlugin->name);
        } catch (\Throwable) {
            // Fail silently; tail update should not block credential updates
        }

        return ProspectResource::collection($credentials);
    }
}
