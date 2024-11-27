<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

interface MailHelperInterface {
    /**
     * @param      $to
     * @param      $title
     * @param      $body
     * @param null $attachment
     *
     * @throws MailNotSentException
     * @throws PHPMailerException
     */
    public function sendPlain($to, $title, $body, $attachment = null): void;

    public function sendViaTemplate(string $to, string $title, string $templatePath, ?array $variables = null, ?string $attachmentPath = null): void;
}
