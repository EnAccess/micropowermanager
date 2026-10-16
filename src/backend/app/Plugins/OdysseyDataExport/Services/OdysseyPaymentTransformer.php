<?php

namespace App\Plugins\OdysseyDataExport\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\ApplianceRate;
use App\Models\Device;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;

class OdysseyPaymentTransformer {
    /**
     * @return array<string, mixed>
     */
    public function transform(PaymentHistory $payment, string $currency, ?string $financingId): array {
        /** @var Token|ApplianceRate|AccessRate|null $paidFor */
        $paidFor = $payment->paidFor;

        $device = match (true) {
            $paidFor instanceof Token => $paidFor->device,
            $paidFor instanceof ApplianceRate => $paidFor->appliancePerson?->device,
            default => null,
        };
        $underlying = $device?->device;
        $meter = $underlying instanceof Meter ? $underlying : null;
        [$latitude, $longitude] = $device?->geo?->latitudeLongitude() ?? [null, null];

        $transactionKwh = null;
        if ($paidFor instanceof Token && $paidFor->token_unit === Token::UNIT_KWH) {
            $transactionKwh = (float) $paidFor->token_amount;
        }

        $payer = $payment->payer instanceof Person ? $payment->payer : null;
        $primaryAddress = $payer?->addresses->firstWhere('is_primary', 1);
        $customerName = $payer instanceof Person ? trim(($payer->name ?? '').' '.($payer->surname ?? '')) : null;

        $customerCategory = $meter?->connectionType->name ?? $this->firstMeterOf($payer)?->connectionType->name;

        $agentId = null;
        $original = $payment->transaction?->originalTransaction;
        if ($original instanceof AgentTransaction) {
            $agentId = $original->agent_id;
        }

        $response = [
            'timestamp' => $payment->created_at?->utc()->format('Y-m-d\TH:i:s.v\Z'),
            'amount' => (float) $payment->amount,
            'currency' => $currency,
            'transactionType' => $this->mapTransactionType($payment),
            'transactionId' => (string) $payment->transaction_id,
            'serialNumber' => $underlying->serial_number ?? 'N/A',
            'customerId' => $payer instanceof Person ? (string) $payer->id : 'N/A',
        ];

        $optionalFields = [
            'meterId' => $meter?->serial_number,
            'transactionKwh' => $transactionKwh,
            'customerName' => $customerName ?: null,
            'customerPhone' => $primaryAddress?->phone,
            'customerCategory' => $customerCategory,
            'financingId' => $financingId,
            'agentId' => $agentId,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'utilityId' => $primaryAddress?->city?->mini_grid_id,
        ];

        return $response + array_filter($optionalFields, fn (string|float|int|null $value): bool => $value !== null);
    }

    private function firstMeterOf(?Person $person): ?Meter {
        $meterDevice = $person?->devices->first(fn (Device $device): bool => $device->device instanceof Meter);

        return $meterDevice?->device;
    }

    private function mapTransactionType(PaymentHistory $payment): string {
        return match ($payment->payment_type) {
            Transaction::TYPE_ENERGY => 'ENERGY_CREDIT',
            'access rate', 'access_rate' => 'FULL_PAYMENT',
            'installment', Transaction::TYPE_DOWN_PAYMENT, Transaction::TYPE_EAAS_RATE => 'INSTALLMENT_PAYMENT',
            Transaction::TYPE_DEFERRED_PAYMENT => 'NON_CONTRACT_PAYMENT',
            default => throw new \UnexpectedValueException("Unmapped payment type '{$payment->payment_type}' for Odyssey export"),
        };
    }
}
