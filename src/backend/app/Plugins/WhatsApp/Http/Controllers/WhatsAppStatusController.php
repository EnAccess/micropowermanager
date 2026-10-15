<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Services\WhatsAppCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppStatusController extends Controller {
    public function __construct(
        private WhatsAppCredentialService $credentialService,
    ) {}

    /**
     * Report whether the integration is configured and enabled.
     */
    public function show(): JsonResponse {
        return response()->json($this->credentialService->getConnectionStatus());
    }
}
