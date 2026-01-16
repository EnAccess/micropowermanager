<?php

namespace App\Plugins\DalyBms\Http\Controllers;

use App\Plugins\DalyBms\Http\Requests\DalyBmsCredentialRequest;
use App\Plugins\DalyBms\Http\Resources\DalyBmsResource;
use App\Plugins\DalyBms\Services\DalyBmsCredentialService;
use Illuminate\Routing\Controller;

class DalyBmsCredentialController extends Controller {
    public function __construct(
        private DalyBmsCredentialService $credentialService,
    ) {}

    public function show(): DalyBmsResource {
        return DalyBmsResource::make($this->credentialService->getCredentials());
    }

    public function update(DalyBmsCredentialRequest $request): DalyBmsResource {
        $credentials = $this->credentialService->getCredentials();
        $updateData = $request->only([
            'user_name',
            'password',
        ]);
        $credentials = $this->credentialService->updateCredentials($credentials, $updateData);

        return DalyBmsResource::make($credentials);
    }
}
