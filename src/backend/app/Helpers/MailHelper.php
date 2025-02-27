<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class MailHelper implements MailHelperInterface {
    /**
     * @var PHPMailer
     */
    private $mailer;

    private $mailSettings;

    public function __construct(PHPMailer $mailer) {
        $this->mailer = $mailer;
        $this->mailSettings = config('mail.mailers.smtp');
        $this->configure();
    }

    private function configure(): void {
        $this->mailer->Host = $this->mailSettings['host'];
        $this->mailer->Port = $this->mailSettings['port'];
        $this->mailer->SMTPSecure = $this->mailSettings['encryption'];
        $this->mailer->SMTPAuth = $this->mailSettings['auth'];
        $this->mailer->Username = $this->mailSettings['username'];
        $this->mailer->Password = $this->mailSettings['password'];
        $this->mailer->From = $this->mailSettings['default_sender'];
        $this->mailer->SMTPDebug = $this->mailSettings['debug_level'];
        $this->mailer->isSMTP();
    }

    /**
     * @param      $to
     * @param      $title
     * @param      $body
     * @param null $attachment
     *
     * @throws MailNotSentException
     * @throws PHPMailerException
     */
    public function sendPlain($to, $title, $body, $attachment = null): void {
        // Only send mails in production environments
        if (config('app.env') != 'production') {
            return;
        }

        $this->mailer->setFrom($this->mailSettings['default_sender']);
        $this->mailer->addReplyTo($this->mailSettings['default_sender']);

        $this->mailer->addAddress($to);

        $this->mailer->Subject = $title;
        $this->mailer->Body = $body;

        if ($attachment) {
            $this->mailer->addAttachment($attachment);
        }

        $this->mailer->AltBody = $this->mailSettings['default_message'];

        if (!$this->mailer->send()) {
            throw new MailNotSentException($this->mailer->ErrorInfo);
        }
    }

    public function sendViaTemplate(string $to, string $title, string $templatePath, ?array $variables = null, ?string $attachmentPath = null): void {
        // Only send mails in production environments
        if (config('app.env') != 'production') {
            return;
        }

        $this->mailer->setFrom($this->mailSettings['default_sender']);
        $this->mailer->addReplyTo($this->mailSettings['default_sender']);

        $this->mailer->addAddress($to);

        $this->mailer->Subject = $title;
        $this->mailer->isHTML(true);
        $this->mailer->Body = view($templatePath, $variables, ['title' => $title])->render();

        if ($attachmentPath) {
            $this->mailer->addAttachment($attachmentPath);
        }

        if (!$this->mailer->send()) {
            throw new MailNotSentException($this->mailer->ErrorInfo);
        }
    }
}
