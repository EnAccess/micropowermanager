<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;

class MailHelperMock implements MailHelperInterface {
    /**
     * @param string      $to
     * @param string      $title
     * @param string      $body
     * @param string|null $attachment
     *
     * @throws MailNotSentException
     */
    public function sendPlain(string $to, string $title, string $body, ?string $attachment = null): void {
        return;
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
        return;
    }
}
