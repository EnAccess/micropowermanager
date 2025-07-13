<?php

namespace App\Helpers;

use App\Exceptions\MailNotSentException;
use Illuminate\Support\Facades\Log;
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
        $this->mailer->Debugoutput = function ($message, $level) {
            Log::debug("PHPMailer [$level]: $message");
        };
        $this->mailer->isSMTP();
        // When debugging Email sending locally it might helpful to explicitly set a Hostname
        // as certain mail providers might block traffic with Hostname `localhost`.
        // And `$_SERVER['SERVER_NAME']` is `localhost` in our local development setup.
        // https://phpmailer.github.io/PHPMailer/classes/PHPMailer-PHPMailer-PHPMailer.html#property_Hostname
        // $this->mailer->Hostname = gethostname();
    }

    /**
     * @param       $to
     * @param       $title
     * @param       $body
     * @param mixed $attachment
     *
     * @throws MailNotSentException
     * @throws PHPMailerException
     */
    public function sendPlain($to, $title, $body, $attachment = null): void {
        if (config('app.env') != 'production') {
            Log::warning('Sending Email is only supported in `production` mode. Running `'.config('app.env').'`');

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
        if (config('app.env') != 'production') {
            Log::warning('Sending Email is only supported in `production` mode. Running `'.config('app.env').'`');

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
