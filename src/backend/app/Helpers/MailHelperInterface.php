<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

interface MailHelperInterface {
    /**
     * @param string      $to
     * @param string      $title
     * @param string      $body
     * @param string|null $attachment
     *
     * @throws MailNotSentException
     * @throws PHPMailerException
     */
    public function sendPlain(string $to, string $title, string $body, ?string $attachment = null): void;

    /**
     * @param array<string, mixed>|null $variables
     */
    public function sendViaTemplate(
        string $to,
        string $title,
        string $templatePath,
        ?array $variables = null,
        ?string $attachmentPath = null,
    ): void;
}
