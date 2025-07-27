<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailHelperStdout implements MailHelperInterface {
    /**
     * @param      $to
     * @param      $title
     * @param      $body
     * @param null $attachment
     *
     * @throws MailNotSentException
     * @throws PHPMailerException
     */
    public function sendPlain($to, $title, $body, ?string $attachment = null): void {
        $this->outputEmail($to, $title, $body, $attachment);
    }

    public function sendViaTemplate(string $to, string $title, string $templatePath, ?array $variables = null, ?string $attachmentPath = null): void {
        $body = view($templatePath, $variables, ['title' => $title])->render();
        $this->outputEmail($to, $title, $body, $attachmentPath);
    }

    /**
     * Output email content to standard output.
     */
    private function outputEmail($to, $title, $body, $attachment = null): void {
        $emailContent = [
            'timestamp' => now()->toISOString(),
            'to' => $to,
            'subject' => $title,
            'body' => $body,
            'attachment' => $attachment,
        ];

        // Output to standard output
        echo "\n".str_repeat('=', 80)."\n";
        echo "EMAIL SENT TO STDOUT (Development/Local Environment)\n";
        echo str_repeat('=', 80)."\n";
        echo 'Timestamp: '.$emailContent['timestamp']."\n";
        echo 'To: '.$emailContent['to']."\n";
        echo 'Subject: '.$emailContent['subject']."\n";
        echo 'Attachment: '.($emailContent['attachment'] ?: 'None')."\n";
        echo str_repeat('-', 80)."\n";
        echo "BODY:\n";
        echo $emailContent['body']."\n";
        echo str_repeat('=', 80)."\n\n";

        // Also log to Laravel log for debugging
        Log::info('Email sent to stdout', $emailContent);
    }
}
