<?php

namespace App\Sms\Senders;

class ManualSms extends SmsSender {
    protected mixed $data;
    public string $body = '';
    protected ?array $references = [
        'body' => '',
    ];

    // With a WhatsApp template the message is that template filled in, so the SMS fallback sends the same words.
    public function prepareBody(): void {
        $this->body .= $this->data['message'];
        $this->whatsappTemplateId = $this->data['template_id'] ?? null;
        $this->bodyParserValues = $this->data['template_values'] ?? [];
    }
}
