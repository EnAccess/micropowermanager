<?php

namespace App\Sms\Senders;

class ManualSms extends SmsSender {
    protected $data;
    public $body = '';
    protected $references = [
        'body' => '',
    ];

    public function prepareBody() {
        $this->body .= $this->data['message'];
    }
}
