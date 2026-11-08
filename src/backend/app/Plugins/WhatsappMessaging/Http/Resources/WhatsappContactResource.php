<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsappContact
 */
class WhatsappContactResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            'id' => $this->id,
            'person_id' => $this->person_id,
            'phone' => $this->phone,
            'wa_id' => $this->wa_id,
            'opt_in_status' => $this->opt_in_status,
            'opted_in_at' => $this->opted_in_at,
            'opted_out_at' => $this->opted_out_at,
            'last_inbound_at' => $this->last_inbound_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'consent_logs' => $this->whenLoaded('consentLogs'),
        ];
    }
}
