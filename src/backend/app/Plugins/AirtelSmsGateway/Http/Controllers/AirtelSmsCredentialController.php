<?php

namespace App\Plugins\AirtelSmsGateway\Http\Controllers;

use App\Plugins\AirtelSmsGateway\Http\Requests\AirtelSmsCredentialRequest;
use App\Plugins\AirtelSmsGateway\Http\Resources\AirtelSmsResource;
use App\Plugins\AirtelSmsGateway\Services\AirtelSmsCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Airtel SMS Gateway', 'API endpoints for integrating with the Airtel SMS gateway (SMPP)')]
class AirtelSmsCredentialController extends Controller {
    public function __construct(
        private AirtelSmsCredentialService $credentialService,
    ) {}

    public function show(): AirtelSmsResource {
        return AirtelSmsResource::make($this->credentialService->getCredentials());
    }

    public function update(AirtelSmsCredentialRequest $request): AirtelSmsResource {
        $credentials = $this->credentialService->updateCredentials([
            'host' => $request->string('host')->toString(),
            'port' => $request->integer('port'),
            'system_id' => $request->string('system_id')->toString(),
            'password' => $request->string('password')->toString(),
            'source_address' => $request->string('source_address')->toString(),
        ]);

        return AirtelSmsResource::make($credentials);
    }
}
