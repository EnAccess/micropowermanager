<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Requests\WhatsAppCredentialRequest;
use App\Plugins\WhatsApp\Http\Resources\WhatsAppResource;
use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppCredentialController extends Controller {
    public function __construct(
        private WhatsAppCredentialService $credentialService,
    ) {}

    /**
     * Display the current WhatsApp credentials and settings.
     */
    public function show(): WhatsAppResource {
        return WhatsAppResource::make($this->credentialService->getCredentials());
    }

    /**
     * Update WhatsApp credentials and settings.
     */
    public function update(WhatsAppCredentialRequest $request): WhatsAppResource {
        $validatedData = $request->validated();
        $credentials = $this->credentialService->updateCredentials($validatedData);

        return WhatsAppResource::make($credentials);
    }
}
