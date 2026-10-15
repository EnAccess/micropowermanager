<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Requests\WhatsAppMessageListRequest;
use App\Plugins\WhatsApp\Http\Resources\WhatsAppMessageResource;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppMessageController extends Controller {
    public function index(WhatsAppMessageListRequest $request): JsonResponse {
        $messages = WhatsAppMessage::query()->select([
            'id', 'message_id', 'recipient_phone', 'recipient_name', 'notification_type', 'reference',
            'status', 'error_message', 'created_at', 'sent_at', 'provider_sent_at', 'delivered_at',
            'read_at', 'failed_at', 'provider_error_code',
        ]);
        if ($request->filled('status')) {
            $messages->where('status', $request->string('status')->toString());
        }
        if ($request->filled('notification_type')) {
            $messages->where('notification_type', $request->string('notification_type')->toString());
        }
        if ($request->filled('date_from')) {
            $messages->where('created_at', '>=', $request->string('date_from')->toString().' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $exclusiveEnd = Carbon::createFromFormat('!Y-m-d', $request->string('date_to')->toString(), 'UTC')->addDay();
            $messages->where('created_at', '<', $exclusiveEnd->format('Y-m-d H:i:s'));
        }
        $page = $messages->latest()->orderByDesc('id')
            ->paginate($request->integer('per_page', 25), ['*'], 'page', $request->integer('page', 1));
        $page->appends($request->validated());

        return WhatsAppMessageResource::collection($page)->response()->header('Cache-Control', 'private, no-store');
    }
}
