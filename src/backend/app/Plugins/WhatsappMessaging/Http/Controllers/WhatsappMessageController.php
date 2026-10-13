<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappMessageFilterRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappMessageResource;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageLogService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp Messaging', 'API endpoints for the WhatsApp message log: every attempt, its delivery and whether SMS took over')]
class WhatsappMessageController extends Controller {
    private const int DEFAULT_PER_PAGE = 15;

    public function __construct(
        private WhatsappMessageLogService $messageLogService,
    ) {}

    public function index(WhatsappMessageFilterRequest $request): AnonymousResourceCollection {
        return WhatsappMessageResource::collection($this->messageLogService->paginate(
            $request->optionalString('status'),
            $request->optionalString('outcome'),
            $request->optionalString('phone'),
            $request->optionalString('from'),
            $request->optionalString('to'),
            $request->filled('per_page') ? $request->integer('per_page') : self::DEFAULT_PER_PAGE,
        ));
    }

    public function summary(WhatsappMessageFilterRequest $request): JsonResponse {
        return response()->json(['data' => $this->messageLogService->summary(
            $request->optionalString('from'),
            $request->optionalString('to'),
        )]);
    }

    public function show(WhatsappMessage $whatsappMessage): WhatsappMessageResource {
        return WhatsappMessageResource::make($this->messageLogService->find($whatsappMessage));
    }
}
