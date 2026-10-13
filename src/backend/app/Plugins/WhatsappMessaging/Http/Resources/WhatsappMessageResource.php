<?php

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Expects a message loaded through WhatsappMessageLogService, which adds the outcome column.
 *
 * @mixin WhatsappMessage
 */
class WhatsappMessageResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $sms = $this->sms;
        $person = $this->contact instanceof WhatsappContact ? $this->contact->person : null;
        $outcome = (string) $this->getAttribute('outcome');

        return [
            'id' => $this->id,
            'sms_id' => $this->sms_id,
            'receiver' => $sms instanceof Sms ? $sms->receiver : null,
            'customer_name' => $person instanceof Person ? trim($person->name.' '.$person->surname) : null,
            'body' => $sms instanceof Sms ? $sms->body : null,
            'message_type' => $this->message_type,
            'template_name' => $this->template_name,
            'template_language' => $this->template_language,
            'whatsapp_status' => $this->status,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'sms_error_message' => $sms instanceof Sms ? $sms->error_message : null,
            'outcome' => $outcome,
            'final_channel' => WhatsappMessage::OUTCOME_FINAL_CHANNELS[$outcome] ?? null,
            'pricing_category' => $this->pricing_category,
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'read_at' => $this->read_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }
}
