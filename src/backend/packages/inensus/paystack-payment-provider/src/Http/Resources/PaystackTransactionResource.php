<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PaystackTransactionResource extends JsonResource {
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
            'meter_serial' => $this->meter_serial,
            'paystack_reference' => $this->paystack_reference,
            'payment_url' => $this->payment_url,
            'metadata' => $this->metadata,
            'attempts' => $this->attempts,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
