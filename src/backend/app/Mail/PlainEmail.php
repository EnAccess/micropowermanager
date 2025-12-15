<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PlainEmail extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(public string $emailSubject, public string $emailBody, public ?string $attachmentPath = null) {
        $this->onConnection('redis');
        $this->onQueue('emails');
    }

    public function build(): self {
        // we use html here to workaround the need of using a template for the email body
        // which laravel demands for the text($this->emailBody) method
        $mail = $this->subject($this->emailSubject)
            ->html($this->emailBody);

        if ($this->attachmentPath && Storage::disk('local')->exists($this->attachmentPath)) {
            $fullPath = Storage::disk('local')->path($this->attachmentPath);
            $mail->attach($fullPath);
        }

        return $mail;
    }
}
