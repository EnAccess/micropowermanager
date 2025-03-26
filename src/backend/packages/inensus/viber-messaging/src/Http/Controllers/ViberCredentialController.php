<?php

namespace Inensus\ViberMessaging\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\URL;
use Inensus\ViberMessaging\Http\Requests\ViberCredentialRequest;
use Inensus\ViberMessaging\Http\Resources\ViberResource;
use Inensus\ViberMessaging\Services\ViberCredentialService;
use MPM\DatabaseProxy\DatabaseProxyManagerService;

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
