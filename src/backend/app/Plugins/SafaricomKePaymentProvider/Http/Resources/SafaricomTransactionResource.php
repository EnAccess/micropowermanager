<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Http\Resources;

use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SafaricomTransaction
 */
class SafaricomTransactionResource extends JsonResource {
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array {
        return [
            'id' => $this->resource->getAttribute('id'),
            'amount' => $this->resource->getAttribute('amount'),
            'currency' => $this->resource->getAttribute('currency'),
            'order_id' => $this->resource->getAttribute('order_id'),
            'reference_id' => $this->resource->getAttribute('reference_id'),
            'status' => $this->resource->getAttribute('status'),
            'external_transaction_id' => $this->resource->getAttribute('external_transaction_id'),
            'customer_id' => $this->resource->getAttribute('customer_id'),
            'serial_id' => $this->resource->getAttribute('serial_id'),
            'device_type' => $this->resource->getAttribute('device_type'),
            'phone_number' => $this->resource->getAttribute('phone_number'),
            'checkout_request_id' => $this->resource->getAttribute('checkout_request_id'),
            'merchant_request_id' => $this->resource->getAttribute('merchant_request_id'),
            'mpesa_receipt_number' => $this->resource->getAttribute('mpesa_receipt_number'),
            'transaction_date' => $this->resource->getAttribute('transaction_date'),
            'account_reference' => $this->resource->getAttribute('account_reference'),
            'transaction_desc' => $this->resource->getAttribute('transaction_desc'),
            'attempts' => $this->resource->getAttribute('attempts'),
            'created_at' => $this->resource->getAttribute('created_at'),
            'updated_at' => $this->resource->getAttribute('updated_at'),
        ];
    }
}
