<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappMessageFilterRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappMessageResource;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Services\WhatsappCredentialService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

#[Group('Plugins / WhatsApp Messaging', 'The WhatsApp message log, delivery failures and what Meta charged')]
class WhatsappMessageController extends Controller {
    public function __construct(private WhatsappMessage $whatsappMessage) {}

    public function index(WhatsappMessageFilterRequest $request): AnonymousResourceCollection {
        $phoneDigits = preg_replace('/\D/', '', $request->string('phone')->toString());

        return WhatsappMessageResource::collection($this->whatsappMessage->newQuery()
            ->with(['sms', 'contact.person'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($phoneDigits !== '', fn ($query) => $query->whereRelation('sms', 'receiver', 'like', '%'.$phoneDigits.'%'))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->string('from')->toString()))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->string('to')->toString()))
            ->latest('id')
            ->paginate($request->filled('per_page') ? $request->integer('per_page') : 15));
    }

    /**
     * Meta's own record of delivered messages and their cost, shown as Meta returns it and never stored (default: the last 30 days).
     */
    public function pricing(WhatsappMessageFilterRequest $request, WhatsappCredentialService $credentialService, WhatsappApiClient $apiClient): JsonResponse {
        $credential = $credentialService->getCredentials() ?? abort(404, 'WhatsApp is not configured.');
        $from = $request->filled('from') ? Carbon::parse($request->string('from')->toString()) : Carbon::now()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->string('to')->toString())->endOfDay()->min(Carbon::now()) : Carbon::now();

        return response()->json(['data' => $apiClient->get($credential, (string) $credential->business_account_id, [
            'fields' => sprintf('currency,pricing_analytics.start(%d).end(%d).granularity(DAILY).dimensions(["PRICING_TYPE"])', $from->getTimestamp(), $to->getTimestamp()),
        ])->json()]);
    }
}
