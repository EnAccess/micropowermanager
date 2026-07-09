<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentStatusResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array {
        return [
            /** @var 'processing'|'processed' Processing state of the payment transaction. */
            'status' => $this->resource['status'],
            /** @var bool True once the transaction has been processed. */
            'processed' => $this->resource['processed'],
            /** @var int ID of the checked transaction. */
            'transaction_id' => $this->resource['transaction_id'],
        ];
    }
}
