<?php

namespace App\Plugins\WhatsappMessaging\Http\Resources;

use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WhatsappMessage
 */
class WhatsappMessageResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        $sms = $this->sms;
        $person = $this->contact instanceof WhatsappContact ? $this->contact->person : null;

        return [
            'id' => $this->id,
            'sms_id' => $this->sms_id,
            'receiver' => $sms instanceof Sms ? $sms->receiver : null,
            'customer_name' => $person instanceof Person ? trim($person->name.' '.$person->surname) : null,
            'body' => $sms instanceof Sms ? $sms->body : null,
            'message_type' => $this->message_type,
            'template_name' => $this->template_name,
            'whatsapp_status' => $this->status,
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'sms_status' => $sms instanceof Sms ? $sms->status : null,
            'sms_error_message' => $sms instanceof Sms ? $sms->error_message : null,
            // After a WhatsApp failure the SMS fallback takes the row over, so its gateway tells which channel finally carried it.
            'final_channel' => $sms instanceof Sms && (int) $sms->gateway_id !== MpmPlugin::WHATSAPP_MESSAGING ? 'sms' : 'whatsapp',
            'sent_at' => $this->sent_at,
            'delivered_at' => $this->delivered_at,
            'read_at' => $this->read_at,
            'failed_at' => $this->failed_at,
            'created_at' => $this->created_at,
        ];
    }
}
