<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Requests\WhatsAppTestMessageRequest;
use App\Plugins\WhatsApp\Http\Resources\WhatsAppMessageResource;
use App\Plugins\WhatsApp\Services\WhatsAppNotificationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppTestMessageController extends Controller {
    public function __construct(
        private WhatsAppNotificationService $notificationService,
    ) {}

    /**
     * Send a text WhatsApp message.
     */
    public function sendTestMessage(WhatsAppTestMessageRequest $request): JsonResponse {
        $message = $this->notificationService->sendTestMessage(
            $request->string('recipient_phone')->toString(),
            $request->has('message') ? $request->string('message')->toString() : null,
        );

        return response()->json([
            'message' => 'Test message sent successfully.',
            'data' => WhatsAppMessageResource::make($message)->resolve(),
        ]);
    }
}
