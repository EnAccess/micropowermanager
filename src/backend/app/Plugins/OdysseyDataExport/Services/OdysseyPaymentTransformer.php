<?php

namespace App\Plugins\OdysseyDataExport\Services;

use App\Models\AccessRate\AccessRate;
use App\Models\ApplianceRate;
use App\Models\MainSettings;
use App\Models\Meter\Meter;
use App\Models\PaymentHistory;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;

class OdysseyPaymentTransformer {
    /**
     * @return array<string, mixed>
     */
    public function transform(PaymentHistory $payment): array {
        $serialNumber = 'N/A';
        $meterId = null;
        $latitude = null;
        $longitude = null;
        $customerCategory = null;

        /** @var Token|ApplianceRate|AccessRate|null $paidFor */
        $paidFor = $payment->paidFor;

        if ($paidFor instanceof Token) {
            $device = $paidFor->device; // App\Models\Device

            if ($device) {
                // Resolve underlying device model for serials
                $underlying = $device->device; // Meter|SolarHomeSystem|EBike
                if ($underlying instanceof Meter) {
                    $meterId = $underlying->serial_number;
                    // Prefer connection type name when available
                    $customerCategory = $underlying->connectionType?->name;
                } elseif ($underlying instanceof SolarHomeSystem) {
                    $serialNumber = $underlying->serial_number;
                }

                // Geo
                $geo = optional($device->geo);
                if ($geo && !empty($geo->points)) {
                    // Expecting "lat,lng" or "lng,lat"; we assume "lat,lng"
                    $parts = explode(',', $geo->points);
                    if (count($parts) === 2) {
                        $latitude = is_numeric($parts[0]) ? $parts[0] : null;
                        $longitude = is_numeric($parts[1]) ? $parts[1] : null;
                    }
                }
            }
        }
        // Handle appliance installment payments paid against ApplianceRate
        elseif ($paidFor instanceof ApplianceRate) {
            $appliancePerson = $paidFor->appliancePerson()->with('device.device', 'device.geo')->first();
            if ($appliancePerson && $appliancePerson->device) {
                $device = $appliancePerson->device;
                $underlying = $device->device;
                if ($underlying instanceof SolarHomeSystem) {
                    $serialNumber = $underlying->serial_number;
                } elseif ($underlying instanceof Meter) {
                    $meterId = $underlying->serial_number;
                    $customerCategory = $underlying->connectionType?->name;
                }

                $geo = optional($device->geo);
                if ($geo && !empty($geo->points)) {
                    $parts = explode(',', $geo->points);
                    if (count($parts) === 2) {
                        $latitude = is_numeric($parts[0]) ? $parts[0] : null;
                        $longitude = is_numeric($parts[1]) ? $parts[1] : null;
                    }
                }
            }
        }

        /** @var Person|null $payer */
        $payer = $payment->payer;
        $customerId = 'N/A';
        $customerName = null;
        $customerPhone = null;

        if ($payer instanceof Person) {
            $customerId = (string) $payer->id;
            $customerName = trim(($payer->name ?? '').' '.($payer->surname ?? ''));

            $primaryAddress = $payer->addresses()->where('is_primary', 1)->first();
            $customerPhone = $primaryAddress?->phone;

            // Fallback: if no category yet, try payer's device connection type
            if (!$customerCategory) {
                $firstDevice = $payer->devices()->with('device')->first();
                if ($firstDevice && $firstDevice->device instanceof Meter) {
                    $firstDevice->device->load('connectionType');
                    $customerCategory = $firstDevice->device->connectionType?->name;
                }
            }
        }

        $currency = MainSettings::query()->value('currency') ?? '';

        // Agent info if originated by AgentTransaction
        $agentId = null;
        $original = $payment->transaction?->originalTransaction;
        if ($original instanceof AgentTransaction) {
            $agentId = $original->agent_id;
        }

        // Build response with only non-null values
        $response = [
            'timestamp' => $payment->created_at?->toISOString(),
            'amount' => (int) $payment->amount,
            'currency' => $currency,
            'transactionType' => $this->mapTransactionType($payment),
            'transactionId' => (string) $payment->transaction_id,
            'serialNumber' => $serialNumber,
            'customerId' => $customerId,
        ];

        // Only include optional fields if they have meaningful values
        if ($meterId !== null) {
            $response['meterId'] = $meterId;
        }
        if ($customerName) {
            $response['customerName'] = $customerName;
        }
        if ($customerPhone !== null) {
            $response['customerPhone'] = $customerPhone;
        }
        if ($customerCategory !== null) {
            $response['customerCategory'] = $customerCategory;
        }
        if ($agentId !== null) {
            $response['agentId'] = $agentId;
        }
        if ($latitude !== null) {
            $response['latitude'] = $latitude;
        }
        if ($longitude !== null) {
            $response['longitude'] = $longitude;
        }

        return $response;
    }

    private function mapTransactionType(PaymentHistory $payment): string {
        // Map internal payment_type to Odyssey types. Default FULL_PAYMENT.
        return match ($payment->payment_type) {
            'energy' => 'FULL_PAYMENT',
            'loan', 'installment' => 'INSTALLMENT_PAYMENT',
            default => 'FULL_PAYMENT',
        };
    }
}
