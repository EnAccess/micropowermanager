<?php

namespace App\Plugins\Prospect\Http\Controllers;

use App\Models\MpmPlugin;
use App\Plugins\Prospect\Http\Resources\ProspectResource;
use App\Plugins\Prospect\Services\ProspectCredentialService;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;

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
            $registrationTail = $this->registrationTailService->getFirst();
            $tailArray = empty($registrationTail->tail) ? [] : json_decode($registrationTail->tail, true);

            $mpmPlugin = $this->mpmPluginService->getById(MpmPlugin::PROSPECT);
            $prospectTag = $mpmPlugin->tail_tag;

            $updated = false;
            foreach ($tailArray as &$item) {
                if (isset($item['tag']) && $item['tag'] === $prospectTag) {
                    $item['adjusted'] = true;
                    $updated = true;
                    break;
                }
            }
            unset($item);

            if ($updated) {
                $this->registrationTailService->update($registrationTail, ['tail' => json_encode($tailArray)]);
            }
        } catch (\Throwable) {
            // Fail silently; tail update should not block credential updates
        }

        return ProspectResource::collection($credentials);
    }
}
