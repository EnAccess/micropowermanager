<?php

namespace App\Sms\Senders;

class ManualSms extends SmsSender {
    protected mixed $data;
    public string $body = '';
    protected ?array $references = [
        'body' => '',
    ];

    public function prepareBody(): void {
        $this->body .= $this->data['message'];
    }
}
