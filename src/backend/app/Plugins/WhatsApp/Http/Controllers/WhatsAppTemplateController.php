<?php

namespace App\Plugins\WhatsApp\Http\Controllers;

use App\Plugins\WhatsApp\Http\Requests\WhatsAppTemplateRequest;
use App\Plugins\WhatsApp\Http\Resources\WhatsAppTemplateResource;
use App\Plugins\WhatsApp\Models\WhatsAppTemplate;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp', 'API endpoints for WhatsApp Business API integration')]
class WhatsAppTemplateController extends Controller {
    /**
     * List every WhatsApp template.
     */
    public function index(): AnonymousResourceCollection {
        return WhatsAppTemplateResource::collection(WhatsAppTemplate::query()->latest('id')->get());
    }

    /**
     * Create a WhatsApp template.
     */
    public function store(WhatsAppTemplateRequest $request): JsonResponse {
        $template = WhatsAppTemplate::query()->create($request->validated());

        return WhatsAppTemplateResource::make($template)->response()->setStatusCode(201);
    }

    /**
     * Update a WhatsApp template.
     */
    public function update(WhatsAppTemplateRequest $request, WhatsAppTemplate $template): WhatsAppTemplateResource {
        $template->update($request->validated());

        return WhatsAppTemplateResource::make($template);
    }
}
