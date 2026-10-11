<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use App\Plugins\WhatsappMessaging\Services\WhatsappWebhookService;
use App\Plugins\WhatsappMessaging\Services\WhatsappWebhookSignature;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging')]
class WhatsappWebhookController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappWebhookSignature $signature,
        private WhatsappWebhookService $webhookService,
    ) {}

    /**
     * PHP rewrites dots in query keys to underscores, so Meta's `hub.mode` arrives as `hub_mode`.
     */
    public function verify(Request $request): Response {
        $verifyToken = $this->credentialService->getCredentials()?->webhook_verify_token;
        $sentToken = $request->string('hub_verify_token')->toString();

        if (
            $request->string('hub_mode')->toString() !== 'subscribe'
            || empty($verifyToken)
            || !hash_equals($verifyToken, $sentToken)
        ) {
            return response('Forbidden', 403);
        }

        return response($request->string('hub_challenge')->toString(), 200)->header('Content-Type', 'text/plain');
    }

    public function receive(Request $request): JsonResponse {
        $credential = $this->credentialService->getCredentials();

        if (!$this->signature->isValid($request, $credential?->app_secret)) {
            return response()->json(['status' => 'forbidden'], 403);
        }

        $this->webhookService->handle($request->json()->all(), (string) $credential->phone_number_id);

        return response()->json(['status' => 'ok']);
    }
}
