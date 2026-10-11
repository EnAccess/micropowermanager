<?php

namespace App\Plugins\WhatsappMessaging\Http\Controllers;

use App\Plugins\WhatsappMessaging\Http\Requests\WhatsappTemplateRequest;
use App\Plugins\WhatsappMessaging\Http\Resources\WhatsappTemplateResource;
use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

#[Group('Plugins / WhatsApp')]
class WhatsappTemplateController extends Controller {
    public function __construct(
        private WhatsappTemplateService $templateService,
    ) {}

    public function index(): AnonymousResourceCollection {
        $metaTemplates = $this->templateService->syncFromMeta();

        return WhatsappTemplateResource::collection(
            $this->templateService->getAll()->map(
                fn (WhatsappTemplate $template): array => $this->templateService->getMappingStatus($template, $metaTemplates),
            ),
        );
    }

    public function update(WhatsappTemplateRequest $request, WhatsappTemplate $template): WhatsappTemplateResource {
        $metaTemplates = $this->templateService->syncFromMeta();

        $template = $this->templateService->update($template, [
            'template_name' => (string) $request->string('template_name'),
            'language_code' => (string) $request->string('language_code'),
            'parameters' => array_values(array_map(strval(...), $request->array('parameters'))),
            'is_active' => $request->boolean('is_active'),
        ], $metaTemplates);

        return WhatsappTemplateResource::make($this->templateService->getMappingStatus($template, $metaTemplates));
    }
}
