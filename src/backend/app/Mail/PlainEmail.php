<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlainEmail extends Mailable {
    use Queueable;
    use SerializesModels;

    public string $emailSubject;
    public string $emailBody;
    public ?string $attachmentPath;

    public function __construct(string $subject, string $body, ?string $attachmentPath = null) {
        $this->onConnection('redis');
        $this->onQueue('emails');

        $this->emailSubject = $subject;
        $this->emailBody = $body;
        $this->attachmentPath = $attachmentPath;
    }

    public function build() {
        $mail = $this->subject($this->emailSubject)
            ->text($this->emailBody);

        if ($this->attachmentPath) {
            $mail->attach($this->attachmentPath);
        }

        return $mail;
    }
}
