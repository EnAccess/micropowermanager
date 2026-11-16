<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappMessageFilterRequest;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

#[Group('Plugins / WhatsApp Messaging', 'What Meta charged for WhatsApp messages')]
class WhatsappMessageController extends Controller {
    public function pricing(WhatsappMessageFilterRequest $request, WhatsappCredentialService $credentialService, WhatsappApiClient $apiClient): JsonResponse {
        $credential = $credentialService->getCredentials() ?? abort(404, 'WhatsApp is not configured.');
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : Carbon::now()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay()->min(Carbon::now()) : Carbon::now();

        return response()->json(['data' => $apiClient->get($credential, (string) $credential->business_account_id, [
            'fields' => sprintf('currency,pricing_analytics.start(%d).end(%d).granularity(DAILY).dimensions(["PRICING_CATEGORY","PRICING_TYPE"])', $from->getTimestamp(), $to->getTimestamp()),
        ])->json()]);
    }
}
