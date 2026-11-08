<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappTemplateResource;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging', 'Templates created in WhatsApp Manager, and which notifications can use them')]
class WhatsappTemplateController extends Controller {
    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private WhatsappTemplate $template,
        private WhatsappTemplateService $templateService,
        private WhatsappCredentialService $credentialService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection {
        return WhatsappTemplateResource::collection($this->template->newQuery()->whereNotIn('name', WhatsappTemplateService::META_SAMPLE_TEMPLATES)->orderBy('name')->orderBy('language')
            ->paginate(min(max($request->integer('per_page', 15), 1), self::MAX_PER_PAGE)));
    }

    /**
     * Each notification's template, and why it goes by SMS when the template cannot be used.
     */
    public function notifications(): JsonResponse {
        return response()->json(['data' => $this->templateService->notificationStatuses()]);
    }

    public function manual(WhatsappMessagingGateway $whatsappGateway): AnonymousResourceCollection {
        return WhatsappTemplateResource::collection($whatsappGateway->isAvailable() ? $this->templateService->manualTemplates() : []);
    }

    /**
     * Reloads the templates from Meta, for changes the webhook has not reported (for example before it was subscribed).
     */
    public function refresh(): Response {
        $credential = $this->credentialService->getCredentials();
        if (!$credential instanceof WhatsappCredential) {
            abort(404, 'WhatsApp is not configured.');
        }

        $this->templateService->refresh($credential);

        return response()->noContent();
    }
}
