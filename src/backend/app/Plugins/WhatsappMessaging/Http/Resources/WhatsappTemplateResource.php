<?php

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Plugins\WhatsappMessaging\Models\WhatsappTemplate;
use App\Plugins\WhatsappMessaging\Services\WhatsappTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WhatsappTemplate */
class WhatsappTemplateResource extends JsonResource {
    /** @return array<string, mixed> */
    public function toArray(Request $request): array {
        return [
            'id' => $this->meta_template_id,
            'name' => $this->name,
            'language' => $this->language,
            'category' => $this->category,
            'status' => $this->status,
            'parameter_format' => $this->parameter_format,
            'header' => $this->text('HEADER'),
            'body' => $this->text('BODY'),
            'parameters' => $this->parameterNames(),
            'can_be_filled_with_text' => $this->canBeFilledWithText(),
            'used_for' => resolve(WhatsappTemplateService::class)->notificationFor($this->resource),
            'rejected_reason' => $this->rejected_reason,
            'rejection_detail' => $this->rejection_detail,
            'rejection_recommendation' => $this->rejection_recommendation,
            'status_detail' => $this->status_detail,
            'updated_at' => $this->updated_at,
        ];
    }
}
