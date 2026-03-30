<?php

namespace App\Plugins\Prospect\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\Transaction;

class ProspectPaymentTransformer {
    /**
     * Transform PaymentHistory to payment time series format.
     *
     * @return array<string, mixed>
     */
    public function transform(PaymentHistory $payment): array {
        $accountExternalId = null;

        /** @var Token|ApplianceRate|AccessRate|null $paidFor */
        $paidFor = $payment->paidFor;
        if ($paidFor instanceof Token) {
            $device = $paidFor->device;
            if ($device) {
                $underlying = $device->device;
                if ($underlying instanceof Meter) {
                    $accountExternalId = $underlying->serial_number;
                } elseif ($underlying instanceof SolarHomeSystem) {
                    $accountExternalId = $underlying->serial_number;
                }
            }
        } elseif ($paidFor instanceof ApplianceRate) {
            $appliancePerson = $paidFor->appliancePerson()->with('device.device')->first();
            if ($appliancePerson && $appliancePerson->device) {
                $underlying = $appliancePerson->device->device;
                if ($underlying instanceof SolarHomeSystem) {
                    $accountExternalId = $underlying->serial_number;
                } elseif ($underlying instanceof Meter) {
                    $accountExternalId = $underlying->serial_number;
                }
            }
        } elseif ($paidFor instanceof AccessRate) {
            $meter = $this->resolveMeterFromTransaction($payment->transaction);

            if (!$meter instanceof Meter) {
                $meter = $paidFor->accessRatePayments()
                    ->with('meter')
                    ->first()?->meter;
            }

            if ($meter) {
                $accountExternalId = $meter->serial_number;
            }
        }

        $currency = MainSettings::query()->value('currency') ?? '';

        // Get provider information from transaction
        $providerName = null;
        $providerCategory = null;
        $providerTransactionId = null;
        $transaction = $payment->transaction;
        if ($transaction) {
            /** @var object|null $originalTransaction */
            $originalTransaction = $transaction->originalTransaction;
            if ($originalTransaction) {
                $providerName = $this->mapProviderName($transaction->original_transaction_type);
                $providerCategory = $this->mapProviderCategory($transaction->original_transaction_type);
                $providerTransactionId = $this->resolveProviderTransactionId($originalTransaction);
            }
        }

        $transactionAmount = $transaction?->amount;

        $purpose = $this->mapTransactionPurpose($transaction?->type);

        $paidAt = $payment->created_at
            ? $payment->created_at->setTimezone('UTC')->format('Y-m-d H:i:s.v').'Z'
            : null;

        return [
            'payment_external_id' => (string) $payment->transaction->id,
            'paid_at' => $paidAt,
            'amount' => (float) ($transactionAmount ?? $payment->amount),
            'currency' => $currency,
            'account_origin' => 'installations',
            'account_external_id' => $accountExternalId,
            'days_active' => null,
            'reverted_at' => null,
            'provider_transaction_id' => $providerTransactionId,
            'provider_category' => $providerCategory,
            'provider_name' => $providerName,
            'purchase_quantity' => null,
            'purchase_unit' => null,
            'purchase_item' => null,
            'paid_until' => null,
            'purpose' => $purpose,
        ];
    }

    /**
     * Map transaction type to Prospect purpose string.
     */
    private function mapTransactionPurpose(?string $transactionType): string {
        return match ($transactionType) {
            'energy' => 'Paygo Payment',
            'deferred_payment', 'loan', 'installment' => 'Loan repayment',
            'access_rate' => 'Access rate payment',
            default => 'Paygo Payment',
        };
    }

    /**
     * Map original_transaction_type to provider name.
     */
    private function mapProviderName(?string $transactionType): ?string {
        if (!$transactionType) {
            return null;
        }

        // Map common transaction types to provider names
        $mapping = [
            'agent_transaction' => 'Agent',
            'cash_transaction' => 'Cash',
            'mesomb_transaction' => 'Mesomb',
            'paystack_transaction' => 'Paystack',
            'swifta_transaction' => 'Swifta',
            'wavecom_transaction' => 'Wavecom',
            'wavemoney_transaction' => 'Wave Money',
        ];

        $type = strtolower($transactionType);
        foreach ($mapping as $key => $name) {
            if (str_contains($type, $key)) {
                return $name;
            }
        }

        return 'Other';
    }

    /**
     * Map original_transaction_type to provider category.
     */
    private function mapProviderCategory(?string $transactionType): ?string {
        if (!$transactionType) {
            return null;
        }

        $type = strtolower($transactionType);

        if (str_contains($type, 'agent') || str_contains($type, 'cash')) {
            return 'cash';
        }

        if (str_contains($type, 'mesomb') || str_contains($type, 'swifta')
            || str_contains($type, 'wavecom') || str_contains($type, 'wavemoney')) {
            return 'mobile money';
        }

        if (str_contains($type, 'paystack')) {
            return 'app';
        }

        return 'other';
    }

    /**
     * Derive the provider transaction identifier from the original transaction payload.
     */
    private function resolveProviderTransactionId(mixed $originalTransaction): ?string {
        if (!$originalTransaction) {
            return null;
        }

        if (method_exists($originalTransaction, 'getTransactionId')) {
            $externalReference = $originalTransaction->getTransactionId();
            if ($externalReference !== null && $externalReference !== '') {
                return (string) $externalReference;
            }
        }

        if (isset($originalTransaction->transaction_id)) {
            return (string) $originalTransaction->transaction_id;
        }

        if ($originalTransaction instanceof AgentTransaction) {
            return 'mpm_agent_transaction_'.$originalTransaction->id;
        }

        if ($originalTransaction instanceof CashTransaction) {
            return 'mpm_cash_transaction_'.$originalTransaction->id;
        }

        if (isset($originalTransaction->id)) {
            return (string) $originalTransaction->id;
        }

        return null;
    }

    private function resolveMeterFromTransaction(?Transaction $transaction): ?Meter {
        $device = $transaction?->device;
        $underlying = $device?->device;

        return $underlying instanceof Meter ? $underlying : null;
    }
}
