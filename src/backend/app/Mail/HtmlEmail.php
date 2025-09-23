<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HtmlEmail extends Mailable {
    use Queueable;
    use SerializesModels;

    public string $emailSubject;
    public string $htmlContent;
    public ?string $attachmentPath;

    public function __construct(string $subject, string $htmlContent, ?string $attachmentPath = null) {
        $this->onConnection('redis');
        $this->onQueue('emails');

        $this->emailSubject = $subject;
        $this->htmlContent = $htmlContent;
        $this->attachmentPath = $attachmentPath;
    }

    public function build() {
        $mail = $this->subject($this->emailSubject)
            ->html($this->htmlContent);

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath);
        }

        return $mail;
    }
}
