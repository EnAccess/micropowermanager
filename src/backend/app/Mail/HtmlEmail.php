<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class HtmlEmail extends Mailable {
    use Queueable;
    use SerializesModels;

    public function __construct(public string $emailSubject, public string $htmlContent, public ?string $attachmentPath = null) {
        $this->onConnection('redis');
        $this->onQueue('emails');
    }

    public function build(): self {
        $mail = $this->subject($this->emailSubject)
            ->html($this->htmlContent);

        if ($this->attachmentPath && Storage::disk('local')->exists($this->attachmentPath)) {
            $fullPath = Storage::disk('local')->path($this->attachmentPath);
            $mail->attach($fullPath);
        }

        return $mail;
    }
}
