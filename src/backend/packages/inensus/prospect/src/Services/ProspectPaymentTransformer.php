<?php

namespace Inensus\Prospect\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\AssetRate;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Token;

class ProspectPaymentTransformer {
    /**
     * Transform PaymentHistory to payment time series format.
     *
     * @return array<string, mixed>
     */
    public function transform(PaymentHistory $payment): array {
        $accountOrigin = null;
        $accountExternalId = null;
        $purchaseItem = null;
        $purchaseUnit = null;
        $purchaseQuantity = null;
        $paidUntil = null;
        $daysActive = null;

        /** @var Token|AssetRate|AccessRate|null $paidFor */
        $paidFor = $payment->paidFor;

        // Determine account_origin and account_external_id
        if ($paidFor instanceof Token) {
            $device = $paidFor->device;
            if ($device) {
                $underlying = $device->device;
                if ($underlying instanceof Meter) {
                    $accountOrigin = 'meters';
                    $accountExternalId = $underlying->serial_number;
                    // For meter tokens, purchase_item is typically "Energy"
                    $purchaseItem = 'Energy';
                    $purchaseUnit = $paidFor->token_unit ?? 'kWh';
                    $purchaseQuantity = $paidFor->token_amount ?? null;
                } elseif ($underlying instanceof SolarHomeSystem) {
                    $accountOrigin = 'shs';
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
                    $accountOrigin = 'shs';
                    $accountExternalId = $underlying->serial_number;
                } elseif ($underlying instanceof Meter) {
                    $accountOrigin = 'meters';
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
                $accountOrigin = 'meters';
                $accountExternalId = $meter->serial_number;
                $purchaseItem = 'Access Rate';
                $purchaseUnit = 'pcs';
                $purchaseQuantity = 1.0;
            }
        }

        // Get payer information for account_external_id fallback
        /** @var Person|null $payer */
        $payer = $payment->payer;
        if (!$accountExternalId && $payer instanceof Person) {
            // Fallback: use person ID as account_external_id if no device found
            $accountExternalId = (string) $payer->id;
            $accountOrigin ??= 'meters'; // Default to meters
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
                // Map original_transaction_type to provider name
                $providerName = $this->mapProviderName($transaction->original_transaction_type);
                $providerCategory = $this->mapProviderCategory($transaction->original_transaction_type);

                // Get provider transaction ID from original transaction
                if (method_exists($originalTransaction, 'getTransactionId')) {
                    $providerTransactionId = $originalTransaction->getTransactionId();
                } elseif (isset($originalTransaction->transaction_id)) {
                    $providerTransactionId = $originalTransaction->transaction_id;
                } elseif (isset($originalTransaction->id)) {
                    $providerTransactionId = (string) $originalTransaction->id;
                }
            }
        }

        // Map payment_type to purpose
        $purpose = $this->mapPurpose($payment->payment_type);

        // Generate payment_external_id (use payment ID or transaction ID)
        $paymentExternalId = 'PAY-'.$payment->id;

        $paidAt = $payment->created_at
            ? $payment->created_at->setTimezone('UTC')->format('Y-m-d H:i:s.v').'Z'
            : null;

        // Build the payment time series data
        $data = [
            'payment_external_id' => $paymentExternalId,
            'purpose' => $purpose,
            'paid_at' => $paidAt,
            'amount' => (float) $payment->amount,
            'currency' => $currency,
            'account_origin' => $accountOrigin ?? 'meters',
            'account_external_id' => $accountExternalId ?? '',
        ];

        // Add optional fields if they have values
        if ($paidUntil !== null) {
            $data['paid_until'] = $paidUntil;
        }

        if ($purchaseItem !== null) {
            $data['purchase_item'] = $purchaseItem;
        }

        if ($purchaseUnit !== null) {
            $data['purchase_unit'] = $purchaseUnit;
        }

        if ($purchaseQuantity !== null) {
            $data['purchase_quantity'] = (float) $purchaseQuantity;
        }

        if ($providerName !== null) {
            $data['provider_name'] = $providerName;
        }

        if ($providerCategory !== null) {
            $data['provider_category'] = $providerCategory;
        }

        if ($providerTransactionId !== null) {
            $data['provider_transaction_id'] = $providerTransactionId;
        }

        if ($daysActive !== null) {
            $data['days_active'] = (float) $daysActive;
        }

        return $data;
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
}
