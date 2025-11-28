<?php

namespace Inensus\Prospect\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\AssetRate;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\CashTransaction;

class ProspectPaymentTransformer {
    /**
     * Transform PaymentHistory to payment time series format.
     *
     * @return array<string, mixed>
     */
    public function transform(PaymentHistory $payment): array {
        $accountExternalId = null;
        $purchaseItem = null;
        $purchaseUnit = null;
        $purchaseQuantity = null;
        $paidUntil = null;

        /** @var Token|AssetRate|AccessRate|null $paidFor */
        $paidFor = $payment->paidFor;

        // Determine account_origin and account_external_id
        if ($paidFor instanceof Token) {
            $device = $paidFor->device;
            if ($device) {
                $underlying = $device->device;
                if ($underlying instanceof Meter) {
                    $accountExternalId = $underlying->serial_number;
                    // For meter tokens, purchase_item is typically "Energy"
                    $purchaseItem = 'Energy';
                    $purchaseUnit = $paidFor->token_unit ?? 'kWh';
                    $purchaseQuantity = $paidFor->token_amount ?? null;
                } elseif ($underlying instanceof SolarHomeSystem) {
                    $accountExternalId = $underlying->serial_number;
                    // For SHS tokens, purchase_item is typically "Uptime"
                    $purchaseItem = 'Uptime';
                    $purchaseUnit = $paidFor->token_unit ?? 'days';
                    $purchaseQuantity = $paidFor->token_amount ?? null;
                }
            }
        } elseif ($paidFor instanceof AssetRate) {
            // Appliance installment payments
            $assetPerson = $paidFor->assetPerson()->with('device.device')->first();
            if ($assetPerson && $assetPerson->device) {
                $underlying = $assetPerson->device->device;
                if ($underlying instanceof SolarHomeSystem) {
                    $accountExternalId = $underlying->serial_number;
                } elseif ($underlying instanceof Meter) {
                    $accountExternalId = $underlying->serial_number;
                }
                $purchaseItem = $assetPerson->asset->name ?? 'Appliance';
                $purchaseUnit = 'pcs';
                $purchaseQuantity = 1.0;
            }
        } elseif ($paidFor instanceof AccessRate) {
            // Access rate payments (typically for meters)
            $meter = $paidFor->meter;
            if ($meter) {
                $accountExternalId = $meter->serial_number;
                $purchaseItem = 'Access Rate';
                $purchaseUnit = 'pcs';
                $purchaseQuantity = 1.0;
            }
        }

        $currency = MainSettings::query()->value('currency') ?? '';

        // Get provider information from transaction
        $providerName = null;
        $providerCategory = null;
        $providerTransactionId = null;
        $transaction = $payment->transaction;
        if ($transaction) {
            $originalTransaction = $transaction->originalTransaction;
            if ($originalTransaction) {
                $providerName = $this->mapProviderName($transaction->original_transaction_type);
                $providerCategory = $this->mapProviderCategory($transaction->original_transaction_type);
                $providerTransactionId = $this->resolveProviderTransactionId($originalTransaction);
            }
        }

        $transactionAmount = $transaction?->amount;

        // Map payment_type to purpose
        $purpose = $this->mapPurpose($payment->payment_type);

        $paidAt = $payment->created_at
            ? $payment->created_at->setTimezone('UTC')->format('Y-m-d H:i:s.v').'Z'
            : null;


        return [
            'payment_external_id' => (string) $payment->transaction->id,
            'paid_at' => $paidAt,
            'purpose' => $purpose,
            'amount' => (float) ($transactionAmount ?? $payment->amount),
            'currency' => $currency,
            'account_origin' => 'installations',
            'account_external_id' => $accountExternalId,
            'days_active' => null,
            'reverted_at' => null,
            'paid_until' => $paidUntil,
            'purchase_item' => $purchaseItem,
            'purchase_unit' => $purchaseUnit,
            'purchase_quantity' => $purchaseQuantity !== null ? (float) $purchaseQuantity : null,
            'provider_name' => $providerName,
            'provider_category' => $providerCategory,
            'provider_transaction_id' => $providerTransactionId,
        ];
    }

    /**
     * Map payment_type to purpose string.
     */
    private function mapPurpose(?string $paymentType): string {
        return match ($paymentType) {
            'energy' => 'Paygo Payment',
            'loan', 'installment' => 'Loan repayment',
            'access_rate' => 'Access Rate Payment',
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

        if (isset($originalTransaction->transaction_id) && $originalTransaction->transaction_id !== null) {
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
}
