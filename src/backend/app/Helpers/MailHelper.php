<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use App\Mail\HtmlEmail;
use App\Mail\PlainEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailHelper {
    /**
     * @param string      $to
     * @param string      $title
     * @param string      $body
     * @param string|null $attachment
     *
     * @throws MailNotSentException
     */
    public function sendPlain(string $to, string $title, string $body, ?string $attachment = null): void {
        try {
            $mailable = new PlainEmail($title, $body, $attachment);
            Mail::to($to)->queue($mailable);

            Log::info("Email sent successfully to: {$to} with subject: {$title}");
        } catch (TransportExceptionInterface $e) {
            Log::error("Failed to send email to {$to}: ".$e->getMessage());
            throw new MailNotSentException('Failed to send email: '.$e->getMessage());
        }
    }

    /**
     * @param array<string, mixed>|null $variables
     */
    public function sendViaTemplate(
        string $to,
        string $title,
        string $templatePath,
        ?array $variables = null,
        ?string $attachmentPath = null,
    ): void {
        try {
            $html = View::make($templatePath, array_merge($variables ?? [], ['title' => $title]))->render();

            $mailable = new HtmlEmail($title, $html, $attachmentPath);
            Mail::to($to)->queue($mailable);
            Log::info("Template email sent successfully to: {$to} with subject: {$title}");
        } catch (TransportExceptionInterface $e) {
            Log::error("Failed to send template email to {$to}: ".$e->getMessage());
            throw new MailNotSentException('Failed to send template email: '.$e->getMessage());
        }
    }
}
