<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Requests\WhatsAppTestMessageRequest;
use App\Plugins\WhatsApp\Services\WhatsAppApiClient;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'Api endpoints for WhatsApp Business API integration')]
class WhatsAppTestMessageController extends Controller {
    public function __construct(
        private WhatsAppApiClient $apiClient,
    ) {}

    /**
     * Send a text WhatsApp message.
     */
    public function sendTestMessage(WhatsAppTestMessageRequest $request): JsonResponse {
        $message = $this->apiClient->sendTestMessage(
            $request->input('recipient_phone'),
            $request->input('message')
        );

        return response()->json([
            'message' => 'Test message sent successfully.',
            'data' => $message,
        ]);
    }
}