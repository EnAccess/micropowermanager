<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappCredentialRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappCredentialResource;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging')]
class WhatsappCredentialController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappTemplateService $templateService,
    ) {}

    public function show(): WhatsappCredentialResource {
        return WhatsappCredentialResource::make($this->credentialService->getCredentials());
    }

    /**
     * Checks the credentials with Meta before saving them, then submits a WhatsApp template for every MPM notification text that has none yet.
     * Templates Meta refused are listed in template_errors; those messages keep going by SMS.
     */
    public function update(WhatsappCredentialRequest $request): WhatsappCredentialResource {
        $credential = $this->credentialService->updateCredentials([
            'phone_number_id' => $request->string('phone_number_id')->toString(),
            'business_account_id' => $request->string('business_account_id')->toString(),
            'access_token' => $request->string('access_token')->toString(),
            'app_secret' => $request->string('app_secret')->toString(),
            'graph_api_version' => $request->string('graph_api_version')->toString(),
        ]);

        return WhatsappCredentialResource::make($credential)->additional(['template_errors' => $this->templateService->sync($credential)]);
    }
}
