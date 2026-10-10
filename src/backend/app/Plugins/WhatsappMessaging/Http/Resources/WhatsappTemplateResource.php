<?php

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{template: WhatsappTemplate, meta_template: array{status: string, category: string|null, placeholder_count: int}|null, problems: list<string>} $resource
 */
class WhatsappTemplateResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $template = $this->resource['template'];
        $metaTemplate = $this->resource['meta_template'];

        return [
            'id' => $template->id,
            'body_parser' => $template->body_parser,
            'template_name' => $template->template_name,
            'language_code' => $template->language_code,
            'parameters' => $template->parameters,
            'is_active' => $template->is_active,
            'meta_status' => $metaTemplate['status'] ?? null,
            'meta_category' => $metaTemplate['category'] ?? null,
            'meta_placeholder_count' => $metaTemplate['placeholder_count'] ?? null,
            'problems' => $this->resource['problems'],
        ];
    }
}
