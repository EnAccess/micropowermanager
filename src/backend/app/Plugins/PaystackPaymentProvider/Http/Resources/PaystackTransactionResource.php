<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Http\Resources;

use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaystackTransaction
 */
class PaystackTransactionResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'order_id' => $this->order_id,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'external_transaction_id' => $this->external_transaction_id,
            'customer_id' => $this->customer_id,
            'serial_id' => $this->serial_id,
            'device_type' => $this->device_type,
            'paystack_reference' => $this->paystack_reference,
            'payment_url' => $this->payment_url,
            'metadata' => $this->metadata,
            'attempts' => $this->attempts,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
