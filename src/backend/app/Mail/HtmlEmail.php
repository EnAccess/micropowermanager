<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\ThrottlesExceptions;
use Illuminate\Queue\SerializesModels;

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

        if ($this->attachmentPath) {
            $mail->attachFromStorage($this->attachmentPath);
        }

        return $mail;
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, RateLimited|ThrottlesExceptions>
     */
    public function middleware(): array {
        return [(new RateLimited('emails'))->releaseAfter(3),
            (new ThrottlesExceptions(3, 5 * 60))->backoff(5),
        ];
    }
}
