<?php

namespace App\Plugins\EgoSmsGateway\Http\Controllers;

use App\Plugins\EgoSmsGateway\Http\Requests\EgoSmsCredentialRequest;
use App\Plugins\EgoSmsGateway\Http\Resources\EgoSmsResource;
use App\Plugins\EgoSmsGateway\Services\EgoSmsCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / EgoSMS Gateway', 'API endpoints for integrating with the EgoSMS gateway')]
class EgoSmsCredentialController extends Controller {
    public function __construct(
        private EgoSmsCredentialService $credentialService,
    ) {}

    public function show(): EgoSmsResource {
        return EgoSmsResource::make($this->credentialService->getCredentials());
    }

    public function update(EgoSmsCredentialRequest $request): EgoSmsResource {
        $credentials = $this->credentialService->updateCredentials([
            'username' => $request->string('username')->toString(),
            'password' => $request->string('password')->toString(),
            'sender_id' => $request->string('sender_id')->toString(),
        ]);

        return EgoSmsResource::make($credentials);
    }
}
