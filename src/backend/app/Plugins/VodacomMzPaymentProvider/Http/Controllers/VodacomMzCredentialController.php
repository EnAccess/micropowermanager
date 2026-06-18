<?php

namespace App\Plugins\VodacomMzPaymentProvider\Http\Controllers;

use App\Plugins\VodacomMzPaymentProvider\Http\Requests\VodacomMzCredentialRequest;
use App\Plugins\VodacomMzPaymentProvider\Http\Resources\VodacomMzCredentialResource;
use App\Plugins\VodacomMzPaymentProvider\Services\VodacomMzCredentialService;
use Illuminate\Routing\Controller;

class VodacomMzCredentialController extends Controller {
    public function __construct(
        private VodacomMzCredentialService $credentialService,
    ) {}

    public function show(): VodacomMzCredentialResource {
        return VodacomMzCredentialResource::make($this->credentialService->getCredentials());
    }

    public function update(VodacomMzCredentialRequest $request): VodacomMzCredentialResource {
        $credentials = $this->credentialService->updateCredentials([
            'api_key' => (string) $request->string('api_key'),
            'public_key' => (string) $request->string('public_key'),
            'service_provider_code' => (string) $request->string('service_provider_code'),
            'live' => $request->boolean('live'),
        ]);

        return VodacomMzCredentialResource::make($credentials);
    }
}
