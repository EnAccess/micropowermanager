<?php

namespace App\Plugins\ViberMessaging\Http\Controllers;

use App\Plugins\ViberMessaging\Http\Requests\ViberCredentialRequest;
use App\Plugins\ViberMessaging\Http\Resources\ViberResource;
use App\Plugins\ViberMessaging\Services\ViberCredentialService;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;

class ViberCredentialController extends Controller {
    public function __construct(
        private ViberCredentialService $credentialService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {}

    public function show(): ViberResource {
        return ViberResource::make($this->credentialService->getCredentials());
    }

    public function update(ViberCredentialRequest $request): ViberResource {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail(auth('api')->user()->email);
        $companyId = $databaseProxy->getCompanyId();
        $apiToken = $request->input('api_token');
        $id = $request->input('id');
        $webhookUrl = URL::to('/')."/api/viber-messaging/webhook/$companyId";

        $credentials = $this->credentialService->updateCredentials([
            'id' => $id,
            'api_token' => $apiToken,
            'webhook_url' => $webhookUrl,
        ]);

        return ViberResource::make($credentials);
    }
}
