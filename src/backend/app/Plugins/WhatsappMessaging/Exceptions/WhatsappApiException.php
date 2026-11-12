<?php

namespace App\Plugins\WhatsappMessaging\Exceptions;

use App\Exceptions\MpmException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

/**
 * Thrown when Meta's Graph API rejects a request or cannot be reached.
 * The message is shown to operators via sms.error_message and API errors, so it states what to do next where Meta's error code allows it.
 * The exception code is Meta's error code, or 0 when Meta was not reached.
 */
class WhatsappApiException extends MpmException {
    private const array OPERATOR_HINTS = [
        190 => 'The WhatsApp access token has expired. Update it in the WhatsApp plugin settings.',
        100 => 'Meta rejected the request payload.',
        130429 => 'The WhatsApp throughput limit was reached.',
        131026 => 'The recipient cannot receive WhatsApp messages.',
        131030 => 'Add the recipient to the allowed list in the Meta dashboard.',
        131031 => 'The WhatsApp Business account is restricted. Contact Meta support.',
        131047 => 'More than 24 hours have passed since the customer last replied. A template message is required.',
        131056 => 'Too many messages were sent to this number in a short time.',
        132000 => 'The template parameters do not match the approved template.',
        132001 => 'The template is not approved yet in WhatsApp Manager.',
        132012 => 'The template parameters do not match the approved template.',
        368 => 'The WhatsApp Business account is restricted. Contact Meta support.',
    ];

    // Meta's codes for its own short outages and for "slow down"; every other error fails the same way when sent again.
    private const array TEMPORARY_META_ERROR_CODES = [1, 2, 130429, 131000, 131016, 133004];

    protected int $httpStatusCode = 502;

    /** True when Meta could not be reached or had a short outage, so the same message can succeed a few minutes later. */
    public function isTemporary(): bool {
        return $this->getPrevious() instanceof ConnectionException || in_array($this->getCode(), self::TEMPORARY_META_ERROR_CODES, true);
    }

    public static function fromErrorResponse(Response $response): self {
        $metaErrorCode = (int) $response->json('error.code', 0);
        $reason = $response->json('error.error_data.details')
            ?? $response->json('error.error_user_msg')
            ?? $response->json('error.message')
            ?? 'HTTP '.$response->status();

        return new self(
            trim(sprintf(
                'WhatsApp Cloud API error (Meta error %d): %s. %s',
                $metaErrorCode,
                rtrim($reason, '.'),
                self::OPERATOR_HINTS[$metaErrorCode] ?? '',
            )),
            $metaErrorCode,
        );
    }
}
