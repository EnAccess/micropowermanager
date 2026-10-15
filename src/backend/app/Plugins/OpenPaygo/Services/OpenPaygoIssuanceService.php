<?php

namespace App\Plugins\OpenPaygo\Services;

use App\Events\TransactionSuccessfulEvent;
use App\Exceptions\Manufacturer\ApiCallDoesNotSupportedException;
use App\Models\Device;
use App\Models\Token;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoDeviceConfigurationException;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoGeneratorException;
use App\Plugins\OpenPaygo\Exceptions\OpenPaygoIssuanceException;
use App\Plugins\OpenPaygo\Http\Clients\OpenPaygoGeneratorClient;
use App\Plugins\OpenPaygo\Models\OpenPaygoDeviceConfiguration;
use App\Plugins\OpenPaygo\Models\OpenPaygoIssuanceReservation;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OpenPaygoIssuanceService {
    private const int MAXIMUM_INPUT_COUNTER = 100000;

    private const int MAXIMUM_RETURNED_COUNTER = 100002;

    public function __construct(private OpenPaygoGeneratorClient $generatorClient) {}

    public function issue(Transaction $transaction, Device $device, string $operation, ?float $creditDays = null): Token {
        if (!in_array($operation, ['credit', 'unlock', 'reset'], true)) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance operation is invalid.');
        }

        $this->validateTransactionDevice($transaction, $device);
        $reservation = $this->reserve($transaction, $device, $operation, $creditDays);

        if ($reservation->state === OpenPaygoIssuanceReservation::STATE_BLOCKED) {
            throw new OpenPaygoIssuanceException($reservation->last_error ?? 'OpenPAYGO configuration must be corrected before issuance.');
        }

        if ($reservation->state === OpenPaygoIssuanceReservation::STATE_COMPLETED) {
            return $transaction->token()->firstOrFail();
        }

        if ($reservation->state === OpenPaygoIssuanceReservation::STATE_GENERATED) {
            return $this->persistStoredResult($reservation->id);
        }

        if ($reservation->state === OpenPaygoIssuanceReservation::STATE_WAITING) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance is waiting for an earlier unresolved request for this device.');
        }

        if ($reservation->state !== OpenPaygoIssuanceReservation::STATE_RESERVED) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance is unresolved and requires recovery.');
        }

        $reservation = $this->markRequesting($reservation->id);
        try {
            $payload = [
                'secretKeyHex' => Crypt::decryptString($reservation->secret_key_ciphertext),
                'startingCode' => $reservation->starting_code,
                'counter' => $reservation->counter,
                'tokenType' => $reservation->generator_operation,
                'restrictedDigitSet' => false,
            ];
            if ($reservation->generator_value !== null) {
                $payload['value'] = $reservation->generator_value;
            }
            $generated = $this->generatorClient->generateToken($payload);
        } catch (\Throwable $exception) {
            $this->markUncertain($reservation->id);

            throw new OpenPaygoIssuanceException('OpenPAYGO generator outcome is uncertain; automatic retry is blocked.', $exception->getCode(), previous: $exception);
        }

        if ($generated['nextCounter'] <= $reservation->counter || $generated['nextCounter'] > self::MAXIMUM_RETURNED_COUNTER) {
            $this->markUncertain($reservation->id);

            throw new OpenPaygoIssuanceException('OpenPAYGO generator returned an invalid next counter.');
        }

        try {
            DB::connection('tenant')->transaction(function () use ($reservation, $generated): void {
                $lockedReservation = OpenPaygoIssuanceReservation::query()
                    ->whereKey($reservation->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedReservation->state !== OpenPaygoIssuanceReservation::STATE_REQUESTING) {
                    throw new OpenPaygoIssuanceException('OpenPAYGO reservation state changed during generation.');
                }

                $lockedReservation->token = $generated['token'];
                $lockedReservation->next_counter = $generated['nextCounter'];
                $lockedReservation->state = OpenPaygoIssuanceReservation::STATE_GENERATED;
                $lockedReservation->save();
            });
        } catch (\Throwable $exception) {
            $this->markUncertain($reservation->id);

            throw new OpenPaygoIssuanceException('Generator response could not be stored; issuance is uncertain and automatic retry is blocked.', $exception->getCode(), previous: $exception);
        }

        return $this->persistStoredResult($reservation->id);
    }

    public function recoverStaleReservations(int $staleAfterMinutes = 10): int {
        $staleBefore = now()->subMinutes(max(1, $staleAfterMinutes));

        $uncertainCount = DB::connection('tenant')->transaction(fn(): int => OpenPaygoIssuanceReservation::query()
            ->where('state', OpenPaygoIssuanceReservation::STATE_REQUESTING)
            ->where('updated_at', '<', $staleBefore)
            ->update([
                'state' => OpenPaygoIssuanceReservation::STATE_UNCERTAIN,
                'updated_at' => now(),
            ]));

        $reservations = OpenPaygoIssuanceReservation::query()
            ->with(['transaction', 'device'])
            ->where(function ($query) use ($staleBefore): void {
                $query->whereIn('state', [
                    OpenPaygoIssuanceReservation::STATE_GENERATED,
                    OpenPaygoIssuanceReservation::STATE_WAITING,
                    OpenPaygoIssuanceReservation::STATE_BLOCKED,
                ])->orWhere(function ($query) use ($staleBefore): void {
                    $query->where('state', OpenPaygoIssuanceReservation::STATE_RESERVED)
                        ->where('updated_at', '<', $staleBefore);
                });
            })
            ->orderBy('id')
            ->get();

        $processed = $uncertainCount;
        foreach ($reservations as $reservation) {
            try {
                $this->issue($reservation->transaction, $reservation->device, $reservation->operation);
                ++$processed;
            } catch (\Throwable $exception) {
                Log::warning('OpenPAYGO reservation remains pending during recovery.', [
                    'reservation_id' => $reservation->id,
                    'transaction_id' => $reservation->transaction_id,
                    'exception' => $exception::class,
                ]);
            }
        }

        return $processed;
    }

    public function reconcileUncertainReservation(int $reservationId, string $token, int $nextCounter): Token {
        if (preg_match('/\A\d{9}\z/', $token) !== 1 || $nextCounter < 1 || $nextCounter > self::MAXIMUM_RETURNED_COUNTER) {
            throw new OpenPaygoIssuanceException('Reconciliation requires a verified nine-digit token and valid next counter.');
        }

        DB::connection('tenant')->transaction(function () use ($reservationId, $token, $nextCounter): void {
            $reference = OpenPaygoIssuanceReservation::query()->findOrFail($reservationId);
            Device::query()->whereKey($reference->device_id)->lockForUpdate()->firstOrFail();
            $reference->transaction()->lockForUpdate()->firstOrFail();
            $reservation = OpenPaygoIssuanceReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();
            $configuration = OpenPaygoDeviceConfiguration::query()
                ->where('device_id', $reservation->device_id)->lockForUpdate()->firstOrFail();
            if ($reservation->state !== OpenPaygoIssuanceReservation::STATE_UNCERTAIN
                || (int) $reservation->active_device_id !== (int) $reservation->device_id
                || $reservation->counter === null
                || $nextCounter <= $reservation->counter
                || (int) $configuration->next_counter !== (int) $reservation->counter) {
                throw new OpenPaygoIssuanceException('Only an active uncertain reservation with an unchanged counter can be reconciled.');
            }

            $reservation->token = $token;
            $reservation->next_counter = $nextCounter;
            $reservation->state = OpenPaygoIssuanceReservation::STATE_GENERATED;
            $reservation->save();
        });

        return $this->persistStoredResult($reservationId);
    }

    private function reserve(
        Transaction $transaction,
        Device $device,
        string $operation,
        ?float $creditDays,
    ): OpenPaygoIssuanceReservation {
        return DB::connection('tenant')->transaction(function () use ($transaction, $device, $operation, $creditDays): OpenPaygoIssuanceReservation {
            $lockedDevice = Device::query()->whereKey($device->getKey())->lockForUpdate()->first();
            if ($lockedDevice === null) {
                throw new OpenPaygoIssuanceException('OpenPAYGO issuance requires a persisted device.');
            }

            $lockedTransaction = Transaction::query()
                ->whereKey($transaction->getKey())
                ->lockForUpdate()
                ->first();

            if ($lockedTransaction === null) {
                throw new OpenPaygoIssuanceException('OpenPAYGO issuance requires a persisted transaction.');
            }

            $this->validateTransactionDevice($lockedTransaction, $lockedDevice);
            $validCreditDays = $creditDays !== null && is_finite($creditDays)
                && $creditDays > 0 && floor($creditDays) === $creditDays;

            $existing = OpenPaygoIssuanceReservation::query()
                ->where('transaction_id', $lockedTransaction->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                if ((int) $existing->device_id !== (int) $device->getKey() || $existing->operation !== $operation) {
                    throw new OpenPaygoIssuanceException('Transaction already has a different OpenPAYGO issuance reservation.');
                }
                if ($operation === 'credit' && $validCreditDays
                    && $existing->state === OpenPaygoIssuanceReservation::STATE_BLOCKED
                    && $existing->counter === null && $existing->credit_days === null) {
                    $existing->credit_days = (int) $creditDays;
                    $existing->save();
                }
                if ($operation === 'credit'
                    && $existing->state !== OpenPaygoIssuanceReservation::STATE_COMPLETED
                    && $creditDays !== null
                    && (!$validCreditDays || (int) $existing->credit_days !== (int) $creditDays)) {
                    throw new OpenPaygoIssuanceException('Transaction already has a different OpenPAYGO credit reservation.');
                }

                return $this->promoteWaitingReservation($existing);
            }

            $this->validateTransactionEligibility($lockedTransaction);

            $reservation = OpenPaygoIssuanceReservation::query()->create([
                'transaction_id' => $lockedTransaction->getKey(),
                'device_id' => $device->getKey(),
                'active_device_id' => null,
                'operation' => $operation,
                'generator_operation' => match ($operation) {
                    'unlock' => 'DISABLE_PAYG',
                    'reset' => 'SET_TIME',
                    'credit' => 'ADD_TIME',
                    default => throw new OpenPaygoIssuanceException('OpenPAYGO issuance operation is invalid.'),
                },
                'credit_days' => $operation === 'credit' && $validCreditDays ? (int) $creditDays : null,
                'state' => OpenPaygoIssuanceReservation::STATE_WAITING,
            ]);

            return $this->promoteWaitingReservation($reservation);
        });
    }

    private function promoteWaitingReservation(OpenPaygoIssuanceReservation $reservation): OpenPaygoIssuanceReservation {
        if (!in_array($reservation->state, [OpenPaygoIssuanceReservation::STATE_WAITING, OpenPaygoIssuanceReservation::STATE_BLOCKED], true)) {
            return $reservation;
        }

        $active = OpenPaygoIssuanceReservation::query()->where('active_device_id', $reservation->device_id)->exists();
        $earlierWaiting = OpenPaygoIssuanceReservation::query()
            ->where('device_id', $reservation->device_id)
            ->whereIn('state', [OpenPaygoIssuanceReservation::STATE_WAITING, OpenPaygoIssuanceReservation::STATE_BLOCKED])
            ->where('id', '<', $reservation->id)
            ->exists();
        if ($active || $earlierWaiting) {
            return $reservation;
        }

        try {
            $configuration = OpenPaygoDeviceConfiguration::query()
                ->where('device_id', $reservation->device_id)->lockForUpdate()->first();
            if ($configuration === null) {
                throw new OpenPaygoDeviceConfigurationException('OpenPAYGO configuration is missing for this device.');
            }
            if ($configuration->next_counter > self::MAXIMUM_INPUT_COUNTER) {
                throw new OpenPaygoIssuanceException('OpenPAYGO counter is exhausted; issuance is blocked before generation.');
            }
            $reservation->generator_value = match ($reservation->operation) {
                'unlock' => null,
                'reset' => 0,
                'credit' => $this->creditValue($reservation->credit_days, $configuration->time_divider),
                default => throw new OpenPaygoIssuanceException('OpenPAYGO issuance operation is invalid.'),
            };
            $this->generatorClient->assertConfigured();
            try {
                Crypt::decryptString($configuration->getRawOriginal('secret_key_hex'));
            } catch (\Throwable) {
                throw new OpenPaygoDeviceConfigurationException('OpenPAYGO secret could not be decrypted.');
            }
        } catch (OpenPaygoDeviceConfigurationException|OpenPaygoIssuanceException|ApiCallDoesNotSupportedException|OpenPaygoGeneratorException $exception) {
            $reservation->state = OpenPaygoIssuanceReservation::STATE_BLOCKED;
            $reservation->last_error = $exception->getMessage();
            $reservation->save();

            return $reservation;
        }

        $reservation->active_device_id = $reservation->device_id;
        $reservation->counter = $configuration->next_counter;
        $reservation->starting_code = $configuration->starting_code;
        $reservation->secret_key_ciphertext = $configuration->getRawOriginal('secret_key_hex');
        $reservation->state = OpenPaygoIssuanceReservation::STATE_RESERVED;
        $reservation->last_error = null;
        $reservation->save();

        return $reservation;
    }

    private function creditValue(?float $creditDays, ?int $timeDivider): int {
        if ($creditDays === null
            || !is_finite($creditDays)
            || $creditDays <= 0
            || floor($creditDays) !== $creditDays) {
            throw new ApiCallDoesNotSupportedException('OpenPAYGO credit issuance requires positive whole credit days calculated by MPM.');
        }

        if ($timeDivider === null || $timeDivider < 1 || $timeDivider > 255) {
            throw new OpenPaygoDeviceConfigurationException('OpenPAYGO time divider is missing or invalid; configure an integer from 1 to 255 for this device.');
        }

        // The OpenPAYGO token customization specification defines each unit as 1 / divider day.
        $value = $creditDays * $timeDivider;
        if (!is_finite($value) || $value < 1 || $value > 995 || floor($value) !== $value) {
            throw new ApiCallDoesNotSupportedException('Calculated OpenPAYGO credit must convert to an integer value from 1 to 995.');
        }

        return (int) $value;
    }

    private function validateTransactionDevice(Transaction $transaction, Device $device): void {
        if (!$transaction->exists || $transaction->getKey() === null) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance requires a persisted transaction.');
        }

        if ($transaction->message === ''
            || $transaction->message !== $device->device_serial) {
            throw new OpenPaygoIssuanceException('OpenPAYGO transaction does not belong to this device.');
        }
    }

    private function validateTransactionEligibility(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->lockForUpdate()->first();

        if ($originalTransaction instanceof BasePaymentProviderTransaction
            && $originalTransaction->status !== BasePaymentProviderTransaction::STATUS_SUCCESS) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance requires a successful payment transaction.');
        }

        if (!$originalTransaction instanceof BasePaymentProviderTransaction
            && $transaction->type !== Transaction::TYPE_AD_HOC) {
            throw new OpenPaygoIssuanceException('OpenPAYGO issuance requires a successful payment transaction.');
        }
    }

    private function markRequesting(int $reservationId): OpenPaygoIssuanceReservation {
        return DB::connection('tenant')->transaction(function () use ($reservationId): OpenPaygoIssuanceReservation {
            $reservation = OpenPaygoIssuanceReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();

            if ($reservation->state !== OpenPaygoIssuanceReservation::STATE_RESERVED) {
                throw new OpenPaygoIssuanceException('OpenPAYGO issuance is unresolved and requires recovery.');
            }

            $reservation->state = OpenPaygoIssuanceReservation::STATE_REQUESTING;
            $reservation->save();

            return $reservation;
        });
    }

    private function markUncertain(int $reservationId): void {
        DB::connection('tenant')->transaction(function () use ($reservationId): void {
            $reservation = OpenPaygoIssuanceReservation::query()->whereKey($reservationId)->lockForUpdate()->first();

            if ($reservation?->state === OpenPaygoIssuanceReservation::STATE_REQUESTING) {
                $reservation->state = OpenPaygoIssuanceReservation::STATE_UNCERTAIN;
                $reservation->save();
            }
        });
    }

    private function persistGeneratedToken(int $reservationId): Token {
        return DB::connection('tenant')->transaction(function () use ($reservationId): Token {
            $reference = OpenPaygoIssuanceReservation::query()->findOrFail($reservationId);
            Device::query()->whereKey($reference->device_id)->lockForUpdate()->firstOrFail();
            $transaction = $reference->transaction()->lockForUpdate()->firstOrFail();
            $reservation = OpenPaygoIssuanceReservation::query()->whereKey($reservationId)->lockForUpdate()->firstOrFail();

            if ($reservation->state === OpenPaygoIssuanceReservation::STATE_COMPLETED) {
                return $reservation->transaction()->firstOrFail()->token()->firstOrFail();
            }

            if ($reservation->state !== OpenPaygoIssuanceReservation::STATE_GENERATED
                || $reservation->token === null
                || $reservation->next_counter === null
                || (int) $reservation->active_device_id !== (int) $reservation->device_id) {
                throw new OpenPaygoIssuanceException('OpenPAYGO generator result is not available for recovery.');
            }

            $configuration = OpenPaygoDeviceConfiguration::query()
                ->where('device_id', $reservation->device_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $configuration->next_counter !== (int) $reservation->counter) {
                throw new OpenPaygoIssuanceException('OpenPAYGO device counter changed during issuance; recovery is required.');
            }

            $token = $transaction->token()->first();
            $tokenType = match ($reservation->operation) {
                'unlock' => Token::TYPE_UNLOCK,
                'reset' => Token::TYPE_RESET,
                'credit' => Token::TYPE_TIME,
                default => throw new OpenPaygoIssuanceException('OpenPAYGO issuance operation is invalid.'),
            };
            $tokenUnit = $reservation->operation === 'credit' ? Token::UNIT_DAYS : null;
            $tokenAmount = $reservation->operation === 'credit' ? $reservation->credit_days : null;

            if ($token === null) {
                $token = Token::query()->create([
                    'device_id' => $reservation->device_id,
                    'transaction_id' => $reservation->transaction_id,
                    'token' => $reservation->token,
                    'token_type' => $tokenType,
                    'token_unit' => $tokenUnit,
                    'token_amount' => $tokenAmount,
                ]);
            } elseif ($token->token !== $reservation->token
                || (int) $token->device_id !== (int) $reservation->device_id
                || $token->token_type !== $tokenType
                || $token->token_unit !== $tokenUnit
                || ($tokenAmount !== null && (float) $token->token_amount !== (float) $tokenAmount)
                || ($tokenAmount === null && $token->token_amount !== null)) {
                throw new OpenPaygoIssuanceException('Stored OpenPAYGO token does not match its generator result.');
            }

            $configuration->next_counter = $reservation->next_counter;
            $configuration->save();

            $reservation->state = OpenPaygoIssuanceReservation::STATE_COMPLETED;
            $reservation->active_device_id = null;
            $reservation->last_error = null;
            $reservation->save();

            if ($transaction->type !== Transaction::TYPE_AD_HOC) {
                event(new TransactionSuccessfulEvent($transaction));
            }

            return $token;
        });
    }

    private function persistStoredResult(int $reservationId): Token {
        try {
            return $this->persistGeneratedToken($reservationId);
        } catch (\Throwable $exception) {
            try {
                OpenPaygoIssuanceReservation::query()->whereKey($reservationId)
                    ->where('state', OpenPaygoIssuanceReservation::STATE_GENERATED)
                    ->update(['last_error' => 'Stored OpenPAYGO generator result requires recovery; no new token will be generated.']);
            } catch (\Throwable) {
                Log::warning('OpenPAYGO recovery status could not be stored.', ['reservation_id' => $reservationId]);
            }
            if ($exception instanceof OpenPaygoIssuanceException) {
                throw $exception;
            }

            throw new OpenPaygoIssuanceException('Stored OpenPAYGO generator result requires recovery; payment rollback is blocked.', $exception->getCode(), previous: $exception);
        }
    }
}
