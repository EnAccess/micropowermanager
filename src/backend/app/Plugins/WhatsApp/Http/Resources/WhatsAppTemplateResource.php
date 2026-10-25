<?php

namespace App\Plugins\WhatsApp\Http\Resources;

use App\Plugins\WhatsApp\Models\WhatsAppTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsAppTemplate
 */
class WhatsAppTemplateResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->typeLabel(),
            'language' => $this->language,
            'body' => $this->body,
            'status' => ucfirst($this->status),
            'updated_at' => $this->updated_at?->toDateString(),
        ];
    }
}
