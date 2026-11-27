<?php

namespace App\Plugins\AirtelPaymentProvider\Http\Controllers;

use App\Plugins\AirtelPaymentProvider\Http\Requests\AirtelCredentialRequest;
use App\Plugins\AirtelPaymentProvider\Http\Resources\AirtelCredentialResource;
use App\Plugins\AirtelPaymentProvider\Services\AirtelCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Airtel Money', 'Endpoints Airtel calls to validate, process and enquire about a payment')]
class AirtelCredentialController extends Controller {
    public function __construct(
        private AirtelCredentialService $credentialService,
    ) {}

    public function show(): AirtelCredentialResource {
        return AirtelCredentialResource::make($this->credentialService->getCredentials());
    }

    public function update(AirtelCredentialRequest $request): AirtelCredentialResource {
        $credentials = $this->credentialService->updateCredentials([
            'jwt_secret' => $request->string('jwt_secret')->toString(),
            'phone_prefix' => $request->string('phone_prefix')->toString(),
        ]);

        return AirtelCredentialResource::make($credentials);
    }
}
