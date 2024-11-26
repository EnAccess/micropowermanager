<?php

namespace Inensus\MesombPaymentProvider\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MesombTransactionProcessingResource extends JsonResource {
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request) {
        return [
            'data' => [
                'type' => 'mesomb_transaction',
                'pk' => $this->pk,
                'attributes' => [
                    'status' => $this->status,
                    'type' => $this->type,
                    'amount' => $this->amount,
                    'fees' => $this->fees,
                    'b_party' => $this->b_party,
                    'message' => $this->message,
                ],
            ],
        ];
    }
}
