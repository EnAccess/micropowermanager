<?php

namespace App\Jobs;

use App\Exceptions\MailNotSentException;
use App\Helpers\MailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

readonly class EmailJobPayload {
    private function __construct(
        public string $to,
        public string $subject,
        public ?string $body,
        public ?string $templatePath,
        /** @var array<string, mixed>|null */
        public ?array $templateVariables,
        public ?string $attachmentPath,
    ) {}

    /**
     * @param array{
     *     to: string,
     *     subject: string,
     *     body?: string|null,
     *     templatePath?: string|null,
     *     templateVariables?: array<string, mixed>|null,
     *     attachmentPath?: string|null
     * } $data
     */
    public static function fromArray(array $data): self {
        return new self(
            to: $data['to'],
            subject: $data['subject'],
            body: $data['body'] ?? null,
            templatePath: $data['templatePath'] ?? null,
            templateVariables: $data['templateVariables'] ?? null,
            attachmentPath: $data['attachmentPath'] ?? null,
        );
    }
}

class SendEmail extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param int             $companyId
     * @param EmailJobPayload $payload
     *
     * @return void
     */
    public function __construct(
        int $companyId,
        private EmailJobPayload $payload,
    ) {
        $this->onConnection('redis');
        $this->onQueue('email');

        parent::__construct($companyId);
    }

    /**
     * Execute the job.
     */
    public function executeJob(): void {
        try {
            // Resolve MailHelper at runtime to avoid serialization issues
            $mailHelper = resolve(MailHelper::class);

            if ($this->payload->templatePath) {
                $mailHelper->sendViaTemplate(
                    $this->payload->to,
                    $this->payload->subject,
                    $this->payload->templatePath,
                    $this->payload->templateVariables,
                    $this->payload->attachmentPath
                );
            } else {
                $mailHelper->sendPlain(
                    $this->payload->to,
                    $this->payload->subject,
                    $this->payload->body,
                    $this->payload->attachmentPath
                );
            }

            Log::info('Email sent successfully', [
                'to' => $this->payload->to,
                'subject' => $this->payload->subject,
                'company_id' => $this->companyId,
            ]);
        } catch (MailNotSentException|PHPMailerException $e) {
            Log::error('Failed to send email', [
                'to' => $this->payload->to,
                'subject' => $this->payload->subject,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::critical('Unexpected error while sending email', [
                'to' => $this->payload->to,
                'subject' => $this->payload->subject,
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
