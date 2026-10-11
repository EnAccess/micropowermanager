<?php

namespace App\Plugins\WhatsappMessaging\Exceptions;

use App\Exceptions\MpmException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

/**
 * Thrown when the WhatsApp Cloud API rejects a request or cannot be reached.
 * The message is shown to operators via sms.error_message, so it states what to do next where Meta's error code allows it.
 */
class WhatsappMessageNotSentException extends MpmException {
    private const array RETRYABLE_META_ERROR_CODES = [130429, 131056];

    private const array OPERATOR_HINTS = [
        190 => 'The WhatsApp access token has expired. Update it in the WhatsApp plugin settings.',
        100 => 'Meta rejected the request payload.',
        130429 => 'The WhatsApp throughput limit was reached.',
        131026 => 'The recipient cannot receive WhatsApp messages.',
        131030 => 'Add the recipient to the allowed list in the Meta dashboard.',
        131031 => 'The WhatsApp Business account is restricted. Contact Meta support.',
        131047 => 'More than 24 hours have passed since the customer last replied. A template message is required.',
        131056 => 'Too many messages were sent to this number in a short time.',
        132000 => 'The template parameters do not match the approved template. Check the template mapping.',
        132001 => 'The template does not exist or is not approved in this language. Check the template mapping.',
        132012 => 'The template parameters do not match the approved template. Check the template mapping.',
        368 => 'The WhatsApp Business account is restricted. Contact Meta support.',
    ];

    protected int $httpStatusCode = 502;

    public function __construct(
        string $message,
        public readonly int $metaErrorCode = 0,
        public readonly ?string $metaErrorMessage = null,
        public readonly ?string $metaErrorDetails = null,
        private readonly bool $isTransientFailure = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $metaErrorCode, $previous);
    }

    public static function fromErrorResponse(Response $response): self {
        $metaErrorCode = (int) $response->json('error.code', 0);
        $metaErrorMessage = $response->json('error.message');
        $metaErrorDetails = $response->json('error.error_data.details');

        $reason = $metaErrorDetails ?? $metaErrorMessage ?? 'HTTP '.$response->status();
        $message = trim(sprintf(
            'WhatsApp Cloud API error (Meta error %d): %s. %s',
            $metaErrorCode,
            rtrim($reason, '.'),
            self::OPERATOR_HINTS[$metaErrorCode] ?? '',
        ));

        return new self(
            $message,
            $metaErrorCode,
            $metaErrorMessage,
            $metaErrorDetails,
            $response->serverError(),
        );
    }

    public static function fromConnectionFailure(ConnectionException $exception): self {
        return new self(
            'Could not reach the WhatsApp Cloud API ('.$exception->getMessage().')',
            isTransientFailure: true,
            previous: $exception,
        );
    }

    public function isRetryable(): bool {
        return $this->isTransientFailure || in_array($this->metaErrorCode, self::RETRYABLE_META_ERROR_CODES, true);
    }
}
