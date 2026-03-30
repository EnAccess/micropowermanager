<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Unified transaction confirmation SMS.
 *
 * Single sender for all channels (Cash, PaymentProvider, etc.). Message is token-focused:
 * - Meter (non-smart): token + energy (kWh).
 * - SHS / PayGo: activation token + duration (e.g. X days).
 * - No token: simple amount confirmation.
 *
 * No header/footer/pricing. Same wording regardless of payment channel.
 */
class TransactionConfirmation extends SmsSender {
    protected mixed $data;
    public string $body = '';

    /** @var array<string, string> */
    protected ?array $references = [
        'body' => '',
    ];

    protected const TOKEN_CONFIRMATION_METER = 'TokenConfirmationMeter';
    protected const TOKEN_CONFIRMATION_SHS = 'TokenConfirmationSHS';
    protected const TRANSACTION_CONFIRMATION_NO_TOKEN = 'TransactionConfirmationNoToken';

    public function prepareBody(): void {
        $transaction = $this->data instanceof Transaction ? $this->data : null;
        if (!$transaction instanceof Transaction) {
            if (is_array($this->data)) {
                $this->prepareBodyByClassReference(self::TRANSACTION_CONFIRMATION_NO_TOKEN, $this->data);
            }

            return;
        }

        $token = $transaction->token()->first();

        if ($token instanceof Token) {
            if ($token->token_type === Token::TYPE_ENERGY) {
                $this->prepareBodyByClassReference(self::TOKEN_CONFIRMATION_METER, $transaction, $token);
            } elseif ($token->token_type === Token::TYPE_TIME) {
                $this->prepareBodyByClassReference(self::TOKEN_CONFIRMATION_SHS, $transaction, $token);
            } else {
                $this->prepareBodyByClassReference(self::TRANSACTION_CONFIRMATION_NO_TOKEN, $transaction);
            }
        } else {
            $this->prepareBodyByClassReference(self::TRANSACTION_CONFIRMATION_NO_TOKEN, $transaction);
        }
    }

    public function getTriggerModel(): ?Model {
        return $this->data instanceof Transaction ? $this->data : null;
    }

    public function getReceiver(): string {
        if ($this->data instanceof Transaction) {
            $t = $this->data;
            $phone = $t->sender;
            if ($phone !== '' && $phone !== '-') {
                $this->receiver = str_starts_with($phone, '+') ? $phone : '+'.$phone;

                return $this->receiver;
            }
            $person = $t->device?->person;
            $person ??= $t->appliance?->person;
            if ($person !== null) {
                $addr = $person->addresses()->where('is_primary', 1)->first();
                $phone = $addr?->phone;
                if ($phone !== null && $phone !== '') {
                    $this->receiver = str_starts_with($phone, '+') ? $phone : '+'.$phone;

                    return $this->receiver;
                }
            }
            throw new \RuntimeException('No phone available for transaction confirmation SMS');
        }
        if (is_array($this->data)) {
            $phone = $this->data['phone'] ?? $this->data['sender'] ?? null;
            if (!in_array($phone, [null, '', '-'], true)) {
                $this->receiver = str_starts_with($phone, '+') ? $phone : '+'.$phone;

                return $this->receiver;
            }
        }
        throw new \RuntimeException('No phone available for transaction confirmation SMS');
    }

    /**
     * @param array<string, mixed>|Transaction|Token ...$payload
     */
    private function prepareBodyByClassReference(string $reference, array|Transaction|Token ...$payload): void {
        try {
            $smsBody = $this->smsBodyService->getSmsBodyByReference($reference);
        } catch (ModelNotFoundException $e) {
            Log::error('SMS body not found: '.$reference, ['message' => $e->getMessage()]);
            throw new MissingSmsReferencesException($reference.' SMS body record not found in database');
        }

        $className = $this->parserSubPath.$reference;
        $smsObject = new $className(...$payload);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Throwable $e) {
            Log::error('SMS body parse failed: '.$reference, ['message' => $e->getMessage()]);
            throw new MissingSmsReferencesException('SMS body parsing failed for '.$reference);
        }
    }
}
