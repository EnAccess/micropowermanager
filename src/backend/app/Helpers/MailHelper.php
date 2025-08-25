<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class MailHelper implements MailHelperInterface {
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
            Mail::raw($body, function ($message) use ($to, $title, $attachment) {
                $message->to($to)->subject($title);

                if ($attachment) {
                    $message->attach($attachment);
                }
            });

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

            Mail::html($html, function ($message) use ($to, $title, $attachmentPath) {
                $message->to($to)->subject($title);

                if ($attachmentPath) {
                    $message->attach($attachmentPath);
                }
            });

            Log::info("Template email sent successfully to: {$to} with subject: {$title}");
        } catch (TransportExceptionInterface $e) {
            Log::error("Failed to send template email to {$to}: ".$e->getMessage());
            throw new MailNotSentException('Failed to send template email: '.$e->getMessage());
        }
    }
}
