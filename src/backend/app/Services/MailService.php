<?php

namespace App\Services;

use Illuminate\Mail\MailManager;
use Illuminate\Mail\Message;

class MailService {
    private MailManager $mailManager;

    public function __construct(MailManager $mailManager) {
        $this->mailManager = $mailManager;
    }

    public function sendWithAttachment(string $view, array $viewData, array $mailMeta, array $attachments) {
        $this->mailManager->send($view, $viewData, function (Message $message) use ($attachments, $mailMeta) {
            $message->to($mailMeta['to'])
                ->from($mailMeta['from'])
                ->subject($mailMeta['title']);
            foreach ($attachments as $attachment) {
                $message->attach($attachment);
            }
        });
    }
}
